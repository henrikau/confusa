<?php
require_once '../confusa_include.php';
require_once 'API.php';
require_once 'CA.php';

/**
 * API class for general information about the authN user (and maybe additional
 * meta-information in the future).
 *
 * Currently this class can return the DN of the logged on person.
 *
 * Call by sending a HTTP GET to an endpoint like
 * https://instance.portal.org/api/infopoint/dn
 * https://instance.portal.org/api/infopoint/dn/rfc2553
 *
 * @since v0.6-rc0
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class API_Infopoint extends API
{
	/* the legal encodings in which the DN can be returned */
	private $DN_FORMATS = array("openssl", "rfc2253");
	/* need a CA instance to get the DN of the person */
	private $ca;

	function __construct()
	{
		parent::__construct();
		$this->ca = CAHandler::getCA($this->person);
	} /* end __construct */

	/**
	 * Generall dispatching handler calling the respective processing functions
	 * matching the request to that API
	 */
	public function processRequest()
	{
		$path = $_SERVER['PATH_INFO'];
		$path = trim($path, "/");

		if (strlen($path) > 0) {
			$this->parameters = explode("/", $path);

			if ($this->parameters[0] === "dn") {
				$this->processInfoRequest();
			}
		}

		$this->errorBadRequest();
	} /* end function processRequest */

	/**
	 * Process requests about the user's DN. If the request is welformed and
	 * can be served, print the full-DN of the user
	 */
	public function processInfoRequest()
	{
		/* default the format to openssl */
		$format = "openssl";
		if (isset($this->parameters[1])) {
			$format = $this->parameters[1];

			if (array_search($format, $this->DN_FORMATS) === FALSE) {
				$this->errorBadRequest();
			}
		}

		switch($format) {
		case "openssl":
			$dnString = $this->ca->getFullDN();
			break;
		case "rfc2253":
			$dnString = $this->ca->getBrowserFriendlyDN();
			break;
		default:
			$dnString = $this->ca->getFullDN();
			break;
		}

		$dnHash = sha1($dnString);
		header("ETag $dnHash");
		echo "DN=$dnString";
		exit(0);
	} /* end processInfoRequest */
} /* end class InfoPointAPI */

$infopointAPI = new API_Infopoint();
$infopointAPI->processRequest();
?>
