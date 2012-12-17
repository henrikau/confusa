<?php
require_once 'Confusa_Auth.php';
require_once 'MapNotFoundException.php';
/**
 * Confusa_Auth_Bypass - The dummy authentication source.
 *
 * Should be active only in test configurations when the respective "auth_bypass"
 * configuration flag is set.
 * Decorates the person object without asking too many questions :)
 *
 * @package auth
 */
class Confusa_Auth_Bypass extends Confusa_Auth
{
	private $attributes;

	function __construct($person)
	{
		parent::__construct($person);
		$this->attributes = array();
		$this->idp = "";

		$personIndex = 0;
		try {
			$personIndex = (int)Config::get_config('bypass_id');
		} catch (KeyNotFoundException $knfe) {
			Logger::log_event(LOG_NOTICE, __FILE__ . ":" . __LINE__ . " bypass_id not set in config. Using default ID.");
		}
		switch ($personIndex) {
		case 0:
			$this->attributes = array(
				'cn2'				=> array('John Doe'),
				'eduPersonPrincipalName'	=> array('jdoe@example.org'),
				'mail2'				=> array('john.doe@example.org'),
				'country'			=> array('NN'),
				'organization'			=> array('o=Hogwarts, dc=hsww, dc=wiz'),
				'nren'				=> array('testnren'),
				'eduPersonEntitlement2'		=> array('urn:mace:feide.no:sigma.uninett.no:confusa'));
			$this->idp = "idp.example.org";
			break;
		case 1:
			$this->attributes = array(
				'cn2'				=> array('Jane Doe'),
				'eduPersonPrincipalName'	=> array('janedoe@example.org'),
				'mail2'				=> array('jane.doe@example.org',
									 'jane@example.org',
									 'janed@example.org'),
				'country'			=> array('NN'),
				'organization'			=> array('o=Barad, dc=Dur'),
				'nren'				=> array('testnren'),
				'eduPersonEntitlement2'		=> array('urn:mace:feide.no:sigma.uninett.no:confusaAdmin',
									 'urn:mace:feide.no:sigma.uninett.no:confusa'));
			$this->idp = "idp.example.org";
			break;
		case 2:
		default:
			$this->attributes = array(
				'cn2'				=> array('Ola Nordmann'),
				'eduPersonPrincipalName'	=> array('onordmann@example.org',
									 'olamann@example.org',
									 'ola@example.org'),
				'mail2'				=> array('ola.nordmann@example.org'),
				'country'			=> array('NO'),
				'organization'			=> array('o=Hogwarts, dc=hsww, dc=wiz'),
				'nren'				=> array('testnren'),
				'eduPersonEntitlement2'		=> array('urn:mace:feide.no:sigma.uninett.no:confusa'));
			$this->idp = "idp.example.org";
			break;
		}

	}

	/**
	 * Decorate the person object with dummy attributes
	 */
	public function authenticate($authRequired)
	{
		session_start();
		$this->person->setAuth(true);
		$this->decoratePerson($this->attributes, $this->idp);
		return $this->person->isAuth();
	}

	public function reAuthenticate()
	{
		$this->person->setAuth(true);
		$this->decoratePerson($this->attributes, $this->idp);
		return $this->person->isAuth();
	}

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function getAttributeKeys($isNRENAdmin = false)
	{
		$res = array();
		foreach ($this->attributes as $key => $value) {
			switch ($key) {
			case "country":
			case "nren":
			case "eduPersonPrincipalName":
				break;
			default:
				$res[] = $key;
				break;
			}
		}
		return $res;
	}

	/**
	 * @see Confusa_Auth::getAttributeValue()
	 */
	public function getAttributeValue($key)
	{
		if (isset($this->attributes[$key])) {
			return $this->attributes[$key];
		} else {
			return "";
		}
	}

	/**
	 * no operation
	 *
	 * @param $logout_loc The location to which the user is redirected
	 */
	public function deAuthenticate($logout_loc = 'logout.php')
	{
		Framework::error_output("Cannot log out, you're in bypass-mode!");
	}

	public function getDiscoPath()
	{
		return "debug_disco.php"
	}
}

?>
