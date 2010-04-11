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

			switch($this->parameters[0]) {
			case "dn":
				$this->processInfoRequest();
				break;
			case "user":
				$this->processAttributeRequest();
				break;
			default:
				$msg = "Allowed commands are infopoint.php/dn and infopoint.php/user!\n";
				$this->errorBadRequest($msg);
				break;
			} /* end switch */
		} /* end if */

		$msg = "Call the infopoint in a form like /api/infopoint.php/dn!\n";
		$this->errorBadRequest($msg);
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
				$msg = "Did not recognize the format ($format) you supplied to the API!\n";
				$this->errorBadRequest($msg);
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

	public function processAttributeRequest()
	{
		$domTree = new DOMDocument('1.0', 'utf-8');
		$userNode = $domTree->createElement("user");
		$uidNode = $domTree->createElement("uid");
		$uidContent = $domTree->createTextNode($this->person->getEPPN());
		$uidNode->appendChild($uidContent);
		$userNode->appendChild($uidNode);

		$nameNode = $domTree->createElement("name");
		$nameContent = $domTree->createTextNode($this->person->getName());
		$nameNode->appendChild($nameContent);
		$userNode->appendChild($nameNode);

		$orgDNNode = $domTree->createElement("orgDN");
		$orgDNContent = $domTree->createTextNode($this->person->getSubscriber()->getOrgName());
		$orgDNNode->appendChild($orgDNContent);
		$userNode->appendChild($orgDNNode);

		$orgIDNode = $domTree->createElement("orgID");
		$orgIDContent = $domTree->createTextNode($this->person->getSubscriber()->getIdPName());
		$orgIDNode->appendChild($orgIDContent);
		$userNode->appendChild($orgIDNode);

		$countryNode = $domTree->createElement("country");
		$countryContent = $domTree->createTextNode($this->person->getCountry());
		$countryNode->appendChild($countryContent);
		$userNode->appendChild($countryNode);

		$nrenNode = $domTree->createElement("nren");
		$nrenContent = $domTree->createTextNode($this->person->getNREN());
		$nrenNode->appendChild($nrenContent);
		$userNode->appendChild($nrenNode);

		$emailsNode = $domTree->createElement("emails");
		$mailList = explode(",", $this->person->getEmail());
		$emailsNodeECAttr = $domTree->createAttribute("elementCount");
		$emailsNodeECACo = $domTree->createTextNode(count($mailList));
		$emailsNodeECAttr->appendChild($emailsNodeECACo);
		$emailsNode->appendChild($emailsNodeECAttr);

		foreach ($mailList as $mail) {
			$emailNode = $domTree->createElement("email");
			$emailNodeContent = $domTree->createTextNode(trim($mail));
			$emailNode->appendChild($emailNodeContent);
			$emailsNode->appendChild($emailNode);
		}

		$userNode->appendChild($emailsNode);

		$enttlsNode = $domTree->createElement("entitlements");
		$enttlList = $this->person->getEntitlement(FALSE);
		$enttlsNodeECAttr = $domTree->createAttribute("elementCount");
		$enttlsNodeECACo = $domTree->createTextNode(count($enttlList));
		$enttlsNodeECAttr->appendChild($enttlsNodeECACo);
		$enttlsNode->appendChild($enttlsNodeECAttr);

		foreach ($enttlList as $enttl) {
			$enttlNode = $domTree->createElement("entitlement");
			$enttlNodeContent = $domTree->createTextNode($enttl);
			$enttlNode->appendChild($enttlNodeContent);
			$enttlsNode->appendChild($enttlNode);
		}

		$userNode->appendChild($enttlsNode);

		$domTree->appendChild($userNode);

		if ($domTree->relaxNGValidate("schema/userInfo.rng") === FALSE) {
			$msg = "The XML-response the portal built appears to be non-conformant to its schema!\n";
			$this->errorInternal($msg);
		}

		$xmlString = $domTree->saveXML();

		$xmlHash = hash("sha256", $xmlString);
		header("ETag: \"$xmlHash\"");
		echo $xmlString;
		exit(0);
	} /* end processAttributeRequest */
} /* end class InfoPointAPI */

$infopointAPI = new API_Infopoint();
$infopointAPI->processRequest();
?>
