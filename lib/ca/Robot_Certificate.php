<?php
require_once 'Certificate.php';
require_once 'key_not_found.php';
require_once 'CertificateException.php';
require_once 'Person.php';
require_once 'MDB2Wrapper.php';
require_once 'Input.php';

class Robot_Certificate extends Certificate
{
	private $owner;		/* admin owning the cert */
	private $subscriber;	/* ID of the subscriber */
	private $lwsent;	/* last warning sent */
	private $uploaded_date;	/* when it was uploaded */
	private $changed;
	private $db_id;

	/**
	 * __construct() create a new Robot_Certificate
	 */
	function __construct($cert)
	{
		parent::__construct($cert);
		/* Not expired? */
		if (!$this->isValid()) {
			throw new CertificateException("The certificate is not valid. Rejected.");
		}
		$this->getCertFromDB(true);
		$this->changed = array();
	}


	/**
	 * setComment() set a comment for the certificate
	 *
	 * When we store a new robot-certificate in the database, the admin may
	 * want to add a note explaining what the certificate will be used for etc.
	 *
	 * Note: this function does *not* store the updated value in the
	 * database. To do that, call save()
	 *
	 * @param	String $comment
	 * @return	Boolean flag indicating if the value was successfully added
	 * @access	public
	 */
	public function setComment($comment)
	{
		if (isset($comment) || $comment != "") {
			$this->comment = $comment;
			$this->changed['comment'] = true;
			return true;
		}
		return false;
	}

	/**
	 * getComment() returns the comment for the certificate
	 *
	 * @param	void
	 * @return	String the comment
	 * @access	public
	 */
	public function getComment()
	{
		if (!isset($this->comment)) {
			return "";
		}
		return $this->comment;
	}

	/**
	 * getCert() return a parsed version of the certificate
	 *
	 * @deprecated use CryptoElement::getPEMContent() instead
	 */
	public function getCert($raw = true)
	{
		return $this->getPEMContent($raw);
	}


	/**
	 * setMadeAvailable() set the date for when the certificate was added to
	 * the datbase
	 *
	 * @param	date $uploaded_date
	 * @return	Boolean flag indicating if the update was successful
	 * @access	public
	 */
	public function setMadeAvailable($uploaded_date)
	{
		if (is_null($uploaded_date)) {
			return false;
		}
		if ($this->uploaded_date == $uploaded_date) {
			return false;
		}
		$this->uploaded_date = $uploaded_date;
		$this->changed['uploaded_date'] = true;
		return true;
	}

	/**
	 * getMadeAvailable() return the date the certificate was uploaded
	 *
	 * @param	void
	 * @return	date the date when it was uploaded
	 * @access	public
	 */
	public function getMadeAvailable()
	{
		return $this->uploaded_date;
	}

	/**
	 * setOwner() set the owner of the certificate.
	 *
	 * The owner should be the id of the admin (found in both robot_certs
	 * and admins)
	 *
	 * @param	Integer $owner the owner of the certificate
	 * @return	boolean flag indicating if the admin was set correctly
	 * @access	public
	 */
	public function setOwner($owner)
	{
		if (is_null($owner)) {
			return false;
		}
		if ($this->owner == $owner) {
			return false;
		}
		$this->owner = $owner;
		$this->changed['owner'] = true;
		return true;
	} /* end setOwner() */


	/**
	 * getOwner() return the admin that uploaded/owns the certificate
	 *
	 * @param	void
	 * @return	Person $admin the owner of the certificate
	 * @access	public
	 */
	public function getOwner()
	{
		return $this->owner;
	}


	/**
	 * setLastWarningSent() set the date for when the last warning about
	 * cert-expiry was sent.
	 *
	 * @param	date $date
	 * @return	Boolean flag indicating if the update was successful
	 * @access	public
	 *
	 */
	public function setLastWarningSent($date)
	{
		/* TODO: define time-format */

		if (!is_set($date)) {
			/* use gettimeofday()? */
			return false;
		}
		/* is a properly formatted time?  */
		$this->lwsent = $date;
		$this->changed['lwsent'] = true;
	}

	/**
	 * getLastWarnngSent() return the date of the last warning sent
	 *
	 * @param	void
	 * @return	date|false the date for when the warning was sent, false
	 *			   if never sent
	 * @access	public
	 */
	public function getLastWarningSent()
	{
		return $this->lwsent;
	}

	/**
	 * save() save the Certificate to the database.
	 *
	 * This function requires that the owner is a registred
	 * administrator. It will then save all the registred fields to the
	 * database and connect the admin to the certificate.
	 *
	 * The function will handle both new certificates as well as updating
	 * existing ones.
	 *
	 * @param	void
	 * @return	Boolean flag indicating if the save-operation succeeded
	 * @access	public
	 */
	public function save()
	{
		/* is it a new certificate? */
		if (!$this->getCertFromDB()) {
			if (!isset($this->owner)) {
				return false;
			}
			try {
				$admin_res = MDB2Wrapper::execute("SELECT * FROM admins WHERE admin_id=?",
								  array('text'),
								  array($this->owner));
				if (count($admin_res) == 0) {
					return false;
				}
				if (count($admin_res) > 1) {
					Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
							  " Corrupted database. Multiple admins with same primary key!");
					return false;
				}
				$this->subscriber = Input::sanitizeID($admin_res[0]['subscriber']);
			} catch (DBStatementException $dbse) {
				Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
						  " Could not find Admin (statement), server said: " .
						  $dbse->getMessage());
				return false;
			} catch (DBQueryException $dbqe) {
				Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
						  " Could not find Admin (query), server said: " .
						  $dbqe->getMessage());
				return false;
			}

			$update  = "INSERT INTO robot_certs (subscriber_id, uploaded_by, ";
			$update .=" uploaded_date, valid_until, cert, fingerprint, ";
			$update .= "serial, comment)";
			$update .= " VALUES(?, ?, current_timestamp(), ?, ?, ?, ?, ?)";
			$params	= array('text', 'text', 'text', 'text', 'text', 'text', 'text');
			$data	= array($this->subscriber,
					$this->owner,
					$this->getEndDate(),
					$this->getPEMContent(),
					$this->getFingerprint(),
					$this->getSerial(),
					$this->getComment());
			echo "$update\n";
			try {
				MDB2Wrapper::update($update, $params, $data);
				return true;
			} catch (DBStatementException $dbse) {
				Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
						  " Could save Robot-cert (statement), server said: " .
						  $dbse->getMessage());
				return false;
			} catch (DBQueryException $dbqe) {
				Logger::log_event(LOG_WARNING, __FILE__ . ":" . __LINE__ .
						  " Could save Robot-cert (query), server said: " .
						  $dbqe->getMessage());
				return false;
			}
		} else if (is_array($this->changed) && count($this->changed) > 0) {
			/* existing certificate, but things have changed. */
			$query = "UPDATE robot_certs SET ";
			$params = array();
			$data = array();
			foreach ($this->changed as $key => $value) {
				$query .= "$key=:$key, ";
				$data[$key] = $value;
			}
			$query = substr($query, 0, -2) . " WHERE id=:id";
			$data['id'] = $this->db_id;
			try {
				MDB2Wrapper::update($query, null, $data);
				echo "updated OK\n";
				return true;
			} catch (DBStatementException $dbse) {
				$msg  = __CLASS__ . "::" . __FUNCTION__ . "(" . __LINE__ . ") ";
				$msg .= "Cannot connect properly to database, some internal error. ";
				$msg .= "Make sure the DB is configured correctly." . $dbse->getMessage();
			} catch (DBQueryException $dbqe) {
				$msg  = __CLASS__ . "::" . __FUNCTION__ . "(" . __LINE__ . ") ";
				$msg .= "Cannot connect properly to database, ";
				$msg .= "errors with supplied data.";
			}
		}
		return false;
	} /* end save() */

	/**
	 * getCertFromDB() take the registred Certificate and find a match in
	 * the DB
	 *
	 * Robot_Certificates are used for authenticating remote
	 * clients. Therefore, we will *always* start the object with a
	 * certificate.
	 *
	 * The authN-mechanism lies in whether or not the certicate is also
	 * present in the database.
	 *
	 * @param	Boolean $db_authoriative the values in the database is
	 *			     authorative (overwrite local values if
	 *			     present).
	 * @return	Boolean flag indicating if the certificate was found and
	 *			     it matches the current
	  @access	private
	 */
	private function getCertFromDB($db_authorative=false)
	{
		$fp = $this->getFingerprint();
		if (!$fp) {
			return false;
		}
		try {
			$query = "SELECT * FROM robot_certs WHERE fingerprint=?";
			$res = MDB2Wrapper::execute($query, array('text'), array($fp));
			if (count($res) == 1) {
				if ($res[0]['cert'] == $this->getPEMContent()) {
					if ($db_authorative) {
						$this->db_id	  = Input::sanitize($res[0]['id']);
						$this->owner	  = Input::sanitize($res[0]['uploaded_by']);
						$this->subscriber = Input::sanitize($res[0]['subscriber_id']);
						$this->lwsent	  = Input::sanitize($res[0]['last_warning_sent']);
						$this->uploaded_date = Input::sanitize($res[0]['uploaded_date']);
					}
					return true;
				}
			}
			return false;
		} catch (DBStatementException $dbse){
			Logger::log_event(LOG_NOTICE, "Corrupted statement in query ("
					  . __FILE__ . ":" . __LINE__ . " " .
					  $dbse->getMessage());
		} catch (DBQueryException $dbqe){
			Logger::log_event(LOG_NOTICE, "Corrupted content in query ("
					  . __FILE__ . ":" . __LINE__ . " " .
					  $dbqe->getMessage());
		}
		return false;

	}
}
?>
