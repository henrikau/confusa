<?php
require_once 'confusa_include.php';
require_once 'mdb2_wrapper.php';
require_once 'CriticalAttributeException.php';

/**
 * Placeholder for an NREN
 *
 * This class contains information about, and operation on this information for
 * an NREN. The class gives the framework a nice, clean way of retrieving
 * per-NREN information¸ and also for storing this information.
 *
 * @author	Henrik Austad <henrik.austad@uninett.no>
 * @license	http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @since	File available since Confusa v0.4-rc0
 */
class NREN
{

	private $map;
	private $hasMap;

	private $data;
	private $pendingChanges;

	function __construct($name)
	{
		$this->data = array();
		$this->data['nren_name'] = $name;
		$this->pendingChanges = false;

		if (!$this->decorateNREN($name)) {
			echo __FILE__ .":".__LINE__." could not decorate NREN (" . $this->name . ")<br />\n";
		}
		$this->retrieveMap();
	}

	function __toString()
	{
		return $this->data['name'];
	}


	/**
	 * dumpDebug() Print debug-info for the class to screen
	 *
	 * This is a debug-function. It will print all content in the data-array
	 * as a way of tracing information etc.
	 *
	 * @param void
	 * @return void
	 */
	function dumpDebug()
	{
		if (!Config::get_config('debug')) {
			echo "WARNING: Running dumpDebug() without debug explicitly set!<br />\n";
		}
		echo "<pre>\n";
		print_r($this->data);
		echo "</pre>\n";
	}


	/**
	 * getName() Return the stored name for the NREN.
	 *
	 * @param	void
	 * @return	String Name of the NREN
	 * @throws	CriticialAttributeException When the name is not set, we
	 *		have stumbled accross internal inconsistency.
	 * @access	public
	 */
	function getName()
	{
		return $this->data['nren_name'];
	}

	/**
	 * getID() return the database ID for the NREN
	 *
	 * If the NREN is stored in the database (which it pretty much must be),
	 * this function will return the Database-ID
	 *
	 * @param	void
	 * @return	Integer|null the ID of the NREN
	 * @access	public
	 */
	function getID()
	{
		return $this->data['nren_id'];
	}

	function getHelp()
	{
		if (is_null($this->data['help'])) {
			$help = MDB2Wrapper::execute("SELECT help FROM nrens WHERE nren_id =?",
						     array('text'),
						     array($this->nren_id));
			if (count($help) == 1 && !is_null($help[0]['help'])) {
				$this->data['help'] = $help[0]['help'];
			}
		}
		return $this->data['help'];
	}

	public function getMap()
	{
		if ($this->hasMap)
			return $this->map;
		return null;
	}

	public function saveMap($epodn, $cn, $mail, $entitlement)
	{
		$doUpdate = false;
		if ($this->hasMap) {
			/* compare value */
			if ($epodn	!= Input::sanitizeText($map['epodn']) ||
			    $cn		!= Input::sanitizeText($map['cn']) ||
			    $mail	!= Input::sanitizeText($map['mail']) ||
			    $entitlement!= Input::sanitizeText($map['entitlement'])) {
				$doUpdate = true;
				$update = "UPDATE attribute_mapping SET epodn=?, cn=?, ".
					" mail=?, entitlement=? WHERE nren_id=? ".
					" AND subscriber_id IS NULL";
				$params = array('text', 'text', 'text', 'text', 'text');
				$data = array(Input::sanitizeText($epodn),
					      Input::sanitizeText($cn),
					      Input::sanitizeText($mail),
					      Input::sanitizeText($entitlement),
					      $this->getID());
			}
		} else {
			$doUpdate = true;
			$update = "INSERT INTO attribute_mapping(nren_id, eppn, epodn, cn, mail, entitlement) VALUES(?, ?, ?, ?, ?, ?)";
			$params = array('text', 'text', 'text', 'text', 'text', 'text');
			$data = array($nren_id[0]['nren_id'], $this->person->getEPPNKey(),
				      $epodn, $cn, $mail, $entitlement);
		}
		if ($doUpdate) {
			try {
				MDB2Wrapper::update($update, $params, $data);
				$this->retrieveMap();
			}catch (DBStatementException $dbse) {
				/* FIXME */
				Framework::error_output(__FILE__ . ":" . __LINE__ . " " . htmlentities($dbse->getMessage()));
				return false;
			} catch (DBQueryException $dbqe) {
				/* FIXME */
				Framework::error_output(__FILE__ . ":" . __LINE__ . " " . htmlentities($dbqe->getMessage()));
				return false;
			}
		}
	}
	/**
	 * Get the contact information for a NREN
	 *
	 * @param void
	 * @return Array The contact-details for the NREN
	 */
	public function getNRENInfo()
	{
		$res = array();
		if (!is_null($this->data['name'])) {
			$res['name']	= Input::sanitizeText($this->data['name']);
		}
		if (!is_null($this->data['help'])) {
			$res['help']	= Input::sanitizeText($this->data['help']);
		}
		if (!is_null($this->data['about'])) {
			$res['about']	= Input::sanitizeText($this->data['about']);
		}
		if (!is_null($this->data['lang'])) {
			$res['lang']	= Input::sanitizeText($this->data['lang']);
		}
		if (!is_null($this->data['contact_email'])) {
			$res['contact_email'] = Input::sanitizeText($this->data['contact_email']);
		}
		if (!is_null($this->data['contact_phone'])) {
			$res['contact_phone'] = Input::sanitizeText($this->data['contact_phone']);
		}
		if (!is_null($this->data['cert_email'])) {
			$res['cert_email'] = Input::sanitizeText($this->data['cert_email']);
		}
		if (!is_null($this->data['cert_phone'])) {
			$res['cert_phone'] = Input::sanitizeText($this->data['cert_phone']);
		}
		if (!is_null($this->data['url'])) {
			$res['url']	= Input::sanitizeText($this->data['url']);
		} else {
			echo "<pre>\n";
			print_r($this->data);
			echo "</pre>\n";
		}

		return $res;
	}

	public function set_login_account($login_account)
	{
		if (!is_null($login_account)) {
			if ($this->data['login_account'] != $login_account) {
				$this->data['login_account'] = Input::sanitizeText($login_account);
				$this->pendingChanges = true;
			}
		}
	}
	public function set_about($about)
	{
		if (!is_null($about)) {
			if ($this->data['about'] != $about) {
				$this->data['about'] = Input::sanitizeText($about);
				$this->pendingChanges = true;
			}
		}
	}
	public function set_help($help)
	{
		if (!is_null($help)) {
			if ($this->data['help'] != $help) {
				$this->data['help'] = Input::sanitizeText($help);
				$this->pendingChanges = true;
			}
		}
	}
	public function set_lang($lang)
	{
		if (!is_null($lang)) {
			if ($this->data['lang'] != $lang) {
				$this->data['lang'] = Input::sanitizeText($lang);
				$this->pendingChanges = true;
			}
		} else {
			echo "Language not set<br />\n";
		}
	}
	public function set_url($url)
	{
		if (!is_null($url)) {
			if ($this->data['url'] != $url) {
				$this->data['url'] = Input::sanitizeText($url);
				$this->pendingChanges = true;
			}
		}
	}
	public function set_contact_email($contact_email)
	{
		if (!is_null($contact_email)) {
			if ($this->data['contact_email'] != $contact_email) {
				$this->data['contact_email'] = Input::sanitizeText($contact_email);
				$this->pendingChanges = true;
			}
		}
	}
	public function set_cert_email($cert_email)
	{
		if (!is_null($cert_email)) {
			if ($this->data['cert_email'] != $cert_email) {
				$this->data['cert_email'] = Input::sanitizeText($cert_email);
				$this->pendingChanges = true;
			}
		}
	}
	public function set_cert_phone($cert_phone)
	{
		if (!is_null($cert_phone)) {
			if ($this->data['cert_phone'] != $cert_phone) {
				$this->data['cert_phone'] = Input::sanitizeText($cert_phone);
				$this->pendingChanges = true;
			}
		}
	}
	public function set_contact_phone($contact_phone)
	{
		if (!is_null($contact_phone)) {
			if ($this->data['contact_phone'] != $contact_phone) {
				$this->data['contact_phone'] = Input::sanitizeText($contact_phone);
				$this->pendingChanges = true;
			}
		}
	}

	/**
	 * saveNREN() Save the current NREN to the database.
	 *
	 * This must be done after new values has been set.
	 *
	 * @param	String	$nren_name The name of the NREN
	 * @return	void
	 * @access	public
	 */
	public function saveNREN()
	{
		if ($this->pendingChanges) {
			$query  = "UPDATE nrens SET contact_email=?, contact_phone=?, ";
			$query .= " cert_phone=?, cert_email=?, url=?, lang=? ";
			$query .= "WHERE nren_id=?";
			$params	= array('text','text', 'text', 'text', 'text', 'text', 'text');
			$data	= array($this->data['contact_email'],
					$this->data['contact_phone'],
					$this->data['cert_phone'],
					$this->data['cert_email'],
					$this->data['url'],
					$this->data['lang'],
					$this->getID());
			try {
				MDB2Wrapper::update($query, $params, $data);
			} catch (DBQueryException $dqe) {
				Framework::error_output("Could not change the NREN contact! Maybe something is " .
							"wrong with the data that you supplied? Server said: " .
							$dqe->getMessage());
				Logger::log_event(LOG_INFO, "[nadm] Could not update " .
						  "contact of NREN $nren: " .
						  $dqe->getMessage());
			} catch (DBStatementException $dse) {
				Framework::error_output("Could not change the NREN contact! Confusa " .
							"seems to be misconfigured. Server said: " .
							$dse->getMessage());
				Logger::log_event(LOG_WARNING, "[nadm] Could not update " .
						  "contact of $nren: " .
						  $dse->getMessage());
				echo $query . "<br />\n";
			}
			Framework::success_output("Updated contact information for your NREN $nren.");
			Logger::log_event(LOG_DEBUG, "[nadm] Updated contact for NREN $nren");
			$this->pendingChanges = false;
		}
	} /* end saveNREN() */
	/**
	 * decorateNREN() Add information about the NREN to the object.
	 *
	 * This function will take the supplied name and query the database for
	 * information. It will store all elements in the row in the object so
	 * it can be used at a later time.
	 *
	 * The database looks like the following:
	 *
	 * +---------------+-------------+------+-----+---------+----------------+
	 * | Field         | Type        | Null | Key | Default | Extra          |
	 * +---------------+-------------+------+-----+---------+----------------+
	 * | nren_id       | int(11)     | NO   | PRI | NULL    | auto_increment |
	 * | name          | varchar(30) | YES  |     | NULL    |                |
	 * | login_account | int(11)     | YES  | MUL | NULL    |                |
	 * | about         | text        | YES  |     | NULL    |                |
	 * | help          | text        | YES  |     | NULL    |                |
	 * | lang          | varchar(5)  | NO   |     | NULL    |                |
	 * | contact_email | varchar(64) | NO   |     | NULL    |                |
	 * | contact_phone | varchar(24) | NO   |     | NULL    |                |
	 * | cert_email    | varchar(64) | YES  |     | NULL    |                |
	 * | cert_phone    | varchar(16) | YES  |     | NULL    |                |
	 * +---------------+-------------+------+-----+---------+----------------+
	 *
	 * We do not retrieve all fields, the large text-fields ('help' and
	 * 'about') are ignored, and will only be retrieved if specifically
	 * asked for.
	 *
	 * @param	String	$nren_name The name of the NREN
	 * @return	void
	 * @access	private
	 */
	private function decorateNREN()
	{
		$query  = "SELECT nren_id, name, login_account, contact_email, ".
			"contact_phone, cert_email, cert_phone, lang, url FROM nrens WHERE name = ?";
		try {
			$res = MDB2Wrapper::execute($query,
						    array('text'),
						    array($this->data['nren_name']));

			switch (count($res)) {
			case 0:
				if (Config::get_config('debug')) {
					echo "no NREN with name (".$this->data['nren_name'].") found in db!<br />\n";
				}
				return false;
			case 1:
				/* decorate NREN */
				foreach ($res[0] as $k => $value) {
					$key = strtolower(Input::sanitizeText($k));
					$this->data[$key] = Input::sanitizeText($value);
				}
				break;
			default:
				echo "too many nrens (" . count($res) . ") found in db. Aborting.<br />\n";
				return false;
			}
		} catch (ConfusaGenException $cge) {
			echo __FILE__ . ":" . __LINE__ . " error with db-connect. " . $cge->getMessage() . "<br />\n";
		}
		return true;
	} /* end decorateNREN() */


	/**
	 * retrieveMap() Retrieve the map for the NREN from the database.
	 *
	 * This function will go to the database and retrieve the map.
	 *
	 * @param	void
	 * @return	void
	 * @access	private
	 */
	private function retrieveMap()
	{
		if (is_null($this->getID())) {
			throw new ConfusaGenException("Cannot find map for NREN when NREN-ID is not set!");
		}
		$this->hasMap = false;

		try {
			$res = MDB2Wrapper::execute("SELECT * FROM attribute_mapping WHERE nren_id=? AND subscriber_id IS NULL",
						    array('text'),
						    array($this->data['nren_id']));
			if (count($res) == 1) {
				$this->hasMap	= true;
				$this->map	= $res[0];
			}
		} catch (ConfusaGenException $e) {
			/* FIXME */
			Framework::error_output(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		}

	} /* end retrievemap() */
} /* end class NREN */
?>