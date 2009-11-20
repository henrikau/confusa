<?php
require_once 'confusa_include.php';
require_once 'framework.php';
require_once 'mdb2_wrapper.php';
require_once 'db_query.php';
require_once 'logger.php';
require_once 'output.php';
require_once 'input.php';

class CP_NREN_Admin extends Content_Page
{
	private $state;
	private $grid_mode = false;
	function __construct()
	{
		parent::__construct("Admin", true);
		$this->org_states	= array('subscribed', 'suspended', 'unsubscribed');
	}


	public function pre_process($person)
	{
		parent::pre_process($person);
		/* If user is not subscriber- or nren-admin, we stop here */
		if (!$this->person->isNRENAdmin())
			return false;


		/* are we running in grid-mode? We must check this before we do
		 * any other processing */
		try {
			if (Config::get_config('obey_grid_restrictions')) {
				$this->grid_mode = true;
				$this->tpl->assign('confusa_grid_restrictions', true);
			}
		} catch (KeyNotFoundException $knfe) {
			Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ . " " .
					  "Cannot find config-switch 'obey_grid_restrictions' (boolean) in confusa-config.");
		}
		/* handle nren-flags */
		if (isset($_POST['subscriber'])) {
			if (isset($_POST['id']))
				$id	= Input::sanitize($_POST['id']);

			if (isset($_POST['state']))
				$state	= Input::sanitize($_POST['state']);

			if (isset($_POST['db_name'])) {
				$db_name	= $_POST['db_name'];
			}

			if (isset($_POST['dn_name'])) {
				$dn_name = Input::sanitize($_POST['dn_name']);
			}

			if(isset($_POST['subscr_email']) && $_POST['subscr_email'] != "") {
				$subscr_email = Input::sanitizeText($_POST['subscr_email']);
			} else {
				$subscr_email = "";
			}
			if(isset($_POST['subscr_phone']) && $_POST['subscr_phone'] != "") {
				$subscr_phone = Input::sanitizeText($_POST['subscr_phone']);
			} else {
				$subscr_phone = "";
			}
			if(isset($_POST['subscr_responsible_name']) && $_POST['subscr_responsible_name'] != "") {
				$subscr_responsible_name = Input::sanitizeText($_POST['subscr_responsible_name']);
			} else {
				$subscr_responsible_name = "";
			}
			if(isset($_POST['subscr_responsible_email']) && $_POST['subscr_responsible_email'] != "") {
					$subscr_responsible_email = Input::sanitizeText($_POST['subscr_responsible_email']);
			} else {
				$subscr_responsible_email = "";
			}
			if(isset($_POST['subscr_comment']) && $_POST['subscr_comment'] != "") {
				$subscr_comment = Input::sanitizeText($_POST['subscr_comment']);
			} else {
				$subscr_comment = "";
			}
			if(isset($_POST['subscr_help_url']) && $_POST['subscr_help_url'] != "") {
				$subscr_help_url = Input::sanitizeText($_POST['subscr_help_url']);
			} else {
				$subscr_help_url= "";
			}
			switch(htmlentities($_POST['subscriber'])) {
			case 'edit':
				$subscriber = null;
				if ($this->person->getSubscriber()->hasDBID($id)) {
					$subscriber = $this->person->getSubscriber();
				} else {
					/* Other subscruber than user's
					 * subscriber, must create new object
					 * from DB */
					$subscriber = Subscriber::getSubscriberByID($_POST['id'], $this->person->getNREN());
				}
				if (!is_null($subscriber)) {
					/* subscriber will clean input */
					$update  = $subscriber->setState(	$_POST['state']);
					$update |= $subscriber->setEmail(	$_POST['subscr_email']);
					$update |= $subscriber->setPhone(	$_POST['subscr_phone']);
					$update |= $subscriber->setRespName(	$_POST['subscr_responsible_name']);
					$update |= $subscriber->setRespEmail(	$_POST['subscr_responsible_email']);
					$update |= $subscriber->setComment(	$_POST['subscr_comment']);
					$update |= $subscriber->setHelpURL(	$_POST['subscr_help_url']);
					if ($update) {
						if (!$subscriber->save(true)) {
							Framework::error_output("Could not update Subscriber, even with changed information.");
						}
					}
				}
				break;

			case 'editState':
				$subscriber = null;
				if ($this->person->getSubscriber()->hasDBID($id)) {
					$subscriber = $this->person->getSubscriber();
				} else {
					$subscriber = Subscriber::getSubscriberByID($_POST['id'], $this->person->getNREN());
				}
				if (!is_null($subscriber)) {
					if ($subscriber->setState($state)) {
						if (!$subscriber->save(true)) {
							Framework::error_output("Could not update state of subscriber. Is the database-layer broken?");
						}
					}
				}
				break;
			case 'info':
				/* get info */
				try {
					$this->tpl->assign('subscriber_details', true);
					$this->tpl->assign('subscriber_detail_id', $id);
				} catch(Exception $e) {
					;
				}
				break;
			case 'add':
				$dn_name = $_POST['dn_name'];
				if ($this->addSubscriber($db_name,
							 $state,
							 $dn_name,
							 $subscr_email,
							 $subscr_phone,
							 $subscr_responsible_name,
							 $subscr_responsible_email,
							 $subscr_help_url,
							 $subscr_comment)) {
					Framework::success_output("Added new subscriber " . htmlentities($dn_name) . " to database.");
				}
				break;
			case 'delete':
				$this->delSubscriber($id);
				break;
			}
		}

	} /* end pre_process */

	public function process()
	{
		if (!$this->person->isNRENAdmin()) {
			Logger::log_event(LOG_NOTICE, "User " . $this->person->getX509ValidCN() . " tried to access the NREN-area");
			$this->tpl->assign('reason', 'You are not an NREN-admin');
			$this->tpl->assign('content', $this->tpl->fetch('restricted_access.tpl'));
			return;
		}

		$this->tpl->assign('nrenName'		, $this->person->getNREN());
		$this->tpl->assign('org_states'		, $this->org_states);

		if (isset($_GET['target'])) {
			switch(Input::sanitize($_GET['target'])) {
			case 'list':
				/* get all info from database and publish to template */
				$this->tpl->assign('subscriber_list'	, $this->getSubscribers());
				$this->tpl->assign('self_subscriber'	, $this->person->getSubscriber()->getIdPName());
				$this->tpl->assign('list_subscribers', true);
				break;
			case 'add':
				$am = AuthHandler::getAuthManager($this->person);
				$attributes = $am->getAttributes();
				$nren = $this->person->getNREN();

				$map = $this->person->getNREN()->getMap();

				if (isset($attributes[$map['epodn']])) {
					$this->tpl->assign('foundUniqueName', $attributes[$map['epodn']][0]);
				}

				$this->tpl->assign('add_subscriber', true);
				break;
			default:
				break;
			}
		} else {
			/* get all info from database and publish to template */
			$this->tpl->assign('subscriber_list'	, $this->getSubscribers());
			$this->tpl->assign('self_subscriber'	, $this->person->getSubscriber()->getIdPName());
			$this->tpl->assign('list_subscribers', true);
		}

		/* render page */
		$this->tpl->assign('content', $this->tpl->fetch('nren_admin.tpl'));

	} /* end process */



	/**
	 * editSubscriber - change an existing subscriber
	 *
	 * Update state and/or subscriber meta-information such as email or
	 * contact-info.
	 *
	 * @name		: The name of the subscriber.
	 * @state		: New state.
	 * @subscr_email	: Contact email for the subscriber
	 * @subscr_phone	: Phone to central place at subscriber's
	 * @subscr_responsible_name	: Someone responsible
	 * @subscr_responsible_email	: That someone's email
	 * @subscr_comment	: Comment.
	 */
	private function editSubscriber($id, $state, $email, $phone, $rname, $remail, $comment)
	{
		try {
			$subscriber = Subscriber::getSubscriberByID($id, $this->person->getNREN());
			$subscriber->setState($state);
			$subscriber->setEmail($email);
			$subscriber->setPhone($phone);
			$subscriber->setRespName($rname);
			$subscriber->setRespEmail($remail);
			$subscriber->setComment($comment);
			$subscriber->save();

			Logger::log_event(LOG_NOTICE, "Updated (full) information for subscriber $subscriber_id");

		} catch (DBStatementException $dbse) {
			Framework::error_output(__FILE__ . ":" . __LINE__ . " Error in query-syntax.<BR />Server said " . $dbse->getMessage());
			Logger::log_event(LOG_NOTICE, "Problem occured when editing the information of subscriber $id: " . $dbse->getMessage());
			return false;
		} catch (DBQueryException $dbqe) {
			Framework::error_output(__FILE__ . ":" . __LINE__ . " Problems with query.<BR />Server said " . $dbqe->getMessage());
			Logger::log_event(LOG_NOTICE, "Problem occured when editing subscriber $id: " . $dbse->getMessage());
			return false;
		}
		return true;
	} /* end editSubscriber */

	/**
	 * addSubscriber - add a new subscriber
	 *
	 * @param db_name String Name of subscriber exported by the IdP. This
	 *			 must be a unique identifier.
	 * @param org_state String The initial state to put the subscriber in.
	 * @param dn_name String The name set by an NREN-admin for this
	 *			 particular subscriber
	 * @param subscr_email String
	 * @param subscr_phone String
	 * @param subscr_responsible_name String
	 * @param subscr_responsible_email String
	 * @param subscr_comment String
	 */
	private function addSubscriber($db_name, $org_state, $dn_name,
				       $subscr_email, $subscr_phone,
				       $subscr_responsible_name, $subscr_responsible_email,
				       $subscr_help_url, $subscr_comment)
	{
		/*
		 * When we add a new subscriber, all attributes must be
		 * set. Those that are not deemed critical are given a default
		 * value (when they are unset, that is).
		 */
		if (!isset($db_name) || $db_name === "") {
			Framework::error_output("Unique, exported orgname (from the IdP) is not set. ".
						"We need this to create a unique key for the subscriber in the database.");
			return false;
		}
		if (!isset($org_state) || $org_state === "") {
			Framework::error_output("orgstate not set, this is required.!");
			return false;
		}
		if (!isset($dn_name) || $dn_name === "") {
			Framework::error_output("The orgname to use in the certificate is not set!");
			return false;
		}
		if (!isset($subscr_email) || $subscr_email === "") {
			Framework::error_output("Need a contact-address for the subscriber.");
			return false;
		}
		if (!isset($subscr_phone) || $subscr_phone == "") {
			$subscr_phone="";
		}
		if (!isset($subscr_responsible_name) || $subscr_responsible_name == "") {
			Framework::error_output("Need a responsible person from the subscriber organization.");
			return false;
		}
		if (!isset($subscr_responsible_email) || $subscr_responsible_email == "") {
			$subscr_responsible_email = "";
		}

		if (!isset($subscr_help_url) || $subscr_help_url == "") {
			$subscr_help_url = "";
		}

		if (!isset($subscr_comment) || $subscr_comment == "") {
			$subscr_comment = "";
		}

		$nren = $this->person->getNREN();
		if (!isset($nren) || $nren === "") {
			Framework::error_output("nren not set!");
			return false;
		}


		/*
		 * Verify length and encoding of dn_name
		 */
		if ($this->grid_mode) {
			$dn_name = Output::mapUTF8ToASCII($dn_name);
			if (strlen($dn_name) > 62) {
				$msg  = "Too long name for subscriber DN-name. ";
				$msg .= "Maximum length is 64 characters. Yours were ";
				$msg .= strlen($dn_name);
				Framework::error_output($msg);
				return false;
			}
		}

		$newSubscriber = new Subscriber($db_name,
								$nren,
								$dn_name,
								$org_state);

		$newSubscriber->setEmail($subscr_email);
		$newSubscriber->setPhone($subscr_phone);
		$newSubscriber->setRespEmail($subscr_responsible_email);
		$newSubscriber->setRespName($subscr_responsible_name);
		$newSubscriber->setComment($subscr_comment);

		try {
			return $newSubscriber->save();
		} catch (ConfusaGenException $cge) {
			Logger::log_event(LOG_NOTICE, $cge->getMessage());
			Framework::error_output(htmlentities($cge->getMessage()));
			return false;
		}
	} /* end addSubscriber() */


	/**
	 * delSubscriber - remove the subscriber from the NREN and Confusa.
	 *
	 * This will remove the subscriber *permanently* along with all it's
	 * affiliated subscriber admins (this is handled by the database-schema
	 * with the 'ON DELETE CASCADE'.
	 *
	 * @param id String|integer the ID of the institution/subscriber in the database.
	 *
	 */
	private function delSubscriber($id) {
		if (!isset($id) || $id === "") {
			Framework::error_output("Cannot delete subscriber with unknown id!");
		}
		$nren	= $this->person->getNREN();

		/*
		 * Make sure that we are deleting a subscriber from the current NREN.
		 */
		try {
			$query  = "SELECT nren_id, subscriber FROM nren_subscriber_view ";
			$query .= "WHERE nren=? AND subscriber_id=?";
			$res =  MDB2Wrapper::execute($query,
						     array('text', 'text'),
						     array($this->person->getNREN(), $id));
		} catch (DBQueryException $dbqe) {
			$msg = "Could not delete subscriber with ID $id from DB.";
			Logger::log_event(LOG_NOTICE, $msg);
			Framework::message_output($msg . "<BR />Server said: " . $dbqe->getMessage());
			return false;
		} catch (DBStatementException $dbse) {
			$msg = "Could not delete subsriber with ID " .htmlentities($id) . " from DB, due to problems with the " .
				"statement. Probably this is a configuration error. Server said: " .
				$dbse->getMessage();
			Logger::log_event(LOG_NOTICE, "ADMIN: " . $msg);
			Framework::message_output($msg);
			return false;
		}

		if (count($res) != 1) {
			Framework::error_output("Could not find a unique NREN/subscriber pair for subscriber with id " .
			                        htmlentities($id));
			return false;
		}
		$nren_id = $res[0]['nren_id'];
		$subscriberName = $res[0]['subscriber'];

		if (!isset($nren_id) || $nren_id == "") {
			Framework::error_output("Could not get the NREN-ID for subscriber " .
			                         htmlentities($id) . "Will not delete subscriber (" .
			                         htmlentites($id) . ").");
			return false;
		}

		/*
		 * Revoke all certificates for subscriber
		 */
		$ca	= CAHandler::getCA($this->person);
		$list	= $ca->getCertListForPersons("", $subscriberName);
		$count	= 0;
		foreach ($list as $key => $value) {
			try {
				if (isset($value['auth_key'])) {
					echo "<pre>\n";
					print_r($value);
					echo "</pre>\n";
					if ($ca->revokeCert($value['auth_key'], "privilegeWithdrawn")) {
						$count = $count + 1;
					}
				}
			}  catch (CGE_KeyRevokeException $kre) {
						echo $kre->getMessage() . "<br />\n";
			}
			Logger::log_event(LOG_INFO, "Deleting subscriber, revoked $count issued certificates ".
					  "for subscriber $subscriberName.");
		}

		MDB2Wrapper::execute("DELETE FROM subscribers WHERE subscriber_id = ? AND nren_id = ?",
				     array('text', 'text'),
				     array($id, $nren_id));

		Logger::log_event(LOG_INFO, "Deleted subscriber with ID $id.\n");
		Framework::message_output("Successfully deleted subscriber (" . htmlentities($subscriberName) . ") with ID " .
		                          htmlentities($id) . ". " .
		                          "A total of $count certificates were also revoked.");
	} /* end delSubscriber */

	/**
	 * getSubscribers - get an array with subscriber and state
	 *
	 * Find all subscribers for the current NREN and return an array containing
	 * - subscriber name
	 * - subscriber state (subscribed | unsubscribed | suspended)
	 *
	 */
	private function getSubscribers()
	{
		try {
			return $this->person->getNREN()->getSubscriberList();
		} catch (DBStatementException $dbse) {
			$msg = __FILE__ . ":" . __LINE__ . " Error in query-syntax. Verify that the query matches the database!";
			Logger::log_event(LOG_NOTICE, $msg);
			$msg .= "<BR />Server said: " . $dbse->getMessage();
			Framework::error_output($msg);
			return;
		} catch (DBQueryException $dbqe) {
			$msg =  __FILE__ . ":" . __LINE__ . " Possible constraint-violation in query. Compare query to db-schema";
			Logger::log_event(LOG_NOTICE, $msg);
			$msg .= "<BR />Server said: " . $dbse->getMessage();
			Framework::error_output($msg);
		}
	} /* end getSubscribers */
}


$fw = new Framework(new CP_NREN_Admin());
$fw->start();

?>
