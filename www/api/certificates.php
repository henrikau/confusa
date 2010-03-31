<?php
require_once '../confusa_include.php';
require_once 'Person.php';
require_once 'Input.php';
require_once 'API.php';

/**
 * REST-API for certificate operations, like requesting new certificates,
 * listing the certificates per user and downloading single certificates.
 *
 * Use the following way
 * HTTP GET /api/certificates.php (list the certificates)
 * HTTP GET /api/certificates.php/<auth-key>/<format> (download a specific
 *      certificate)
 * HTTP POST csr /api/certificates.php (upload a certificate signing request to
 *      the API)
 *
 * @since v0.6-rc0
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class API_Certificates extends API
{
	/* the ca backend that is used for most certificate operations */
	private $ca;
	private $CERT_FORMATS = array('PKCS7_CABUNDLE', 'PKCS7_NOCHAIN');

	function __construct()
	{
		parent::__construct();
		$this->ca = CAHandler::getCA($this->person);
		$perm = $this->person->mayRequestCertificate();

		if (!$perm->isPermissionGranted()) {
			$this->errorNotAuthorized($perm);
		}
	} /* end constructor */

	/**
	 * Process a request to this endpoint. Usually those requests are about
	 * requesting, downloading and listing certificates
	 *
	 * The API is mostly easy for the caller, detecting what the caller meant
	 * on our side is unfortunately not so easy. So what the function does
	 *
	 * 1.) Does the request generate a POST? If so and if it includes POST['csr']
	 *     ship the CSR to signing
	 * 2.) Does the path to script have suffix parameters? If so, the first
	 *     suffix parameter is the auth-key/order-number of the certificate
	 *     which should be returned
	 * 3.) If there is no suffix, list all the available certificates of the
	 *     authN user
	 */
	public function processRequest()
	{
		if (!$this->person->isAuth()) {
			$this->errorAuth();
		}

		/* ship the CSR to signing */
		if (isset($_POST['csr'])) {
			$this->processSigningRequest(Input::sanitizeBase64($_POST['csr']));
		}

		$path = $_SERVER['PATH_INFO'];
		$path = trim($path, "/");
		if (strlen($path) > 0) {
			$this->parameters=explode("/", $path);
		}

		if (count($this->parameters) >= 1) {
			$this->processDownloadSingle();
		}

		$this->processListCerts();
	} /* end processRequest */

	/**
	 * Download a single certificate, identified by some sort of auth-key
	 * (order-number, hash-like auth-key)
	 * FIXME format currently ignored
	 */
	public function processDownloadSingle()
	{
		/* FIXME return a separate status code if the certificate does not
		 * exist (404), wait for better CA interface :)
		 */
		/* FIXME return 202 if the cert is still being processed, wait for
		 * a better CA interface for that */

		$auth_key = Input::sanitizeCertKey($this->parameters[0]);

		if (isset($this->parameters[1])) {
			$format = $this->parameters[1];

			if (array_search(strtoupper($format), $this->CERT_FORMATS) === FALSE) {
				$msg  = "If you want to query for certificates, do HTTP GET on an URL like:\n";
				$msg .= "/api/certifificates.php/<auth-key>/<cert-format> where:\n";
				$msg .= "\t\t<auth-key>:\tUnique identifier of the certificate.\n";
				$msg .= "\t\t<cert-format>:\tThe format of the certificate, one of " .
				        implode(",", $this->CERT_FORMATS) . "\n";
				$this->errorBadRequest($msg);
			}
		}

		$cert = $this->ca->getCert($auth_key);
		$certHash = hash("sha256", $cert);
		header("ETag: \"$certHash\"");
		echo "cert=$cert";
		exit(0);
	} /* end processDownloadSingle */

	/**
	 * return a list of all the certificates of the authN user, currently
	 * in XML format.
	 */
	public function processListCerts()
	{
		$list = $this->ca->getCertList();
		$domTree = new DOMDocument('1.0', 'utf-8');
		$certificates = $domTree->createElement("certificates");

		$certificatesCount = $domTree->createAttribute("elementCount");
		$certCountValue = $domTree->createTextNode((string) count($list));
		$certificatesCount->appendChild($certCountValue);
		$certificates->appendChild($certificatesCount);

		if (count($list) > 0) {
			foreach ($list as $row) {
				$certificate = $domTree->createElement("certificate");

				$id = $domTree->createElement("id");
				$certID = $row['order_number'];
				$idContent = $domTree->createTextNode("/api/certificates/$certID");
				$id->appendChild($idContent);
				$certificate->appendChild($id);

				$status = $domTree->createElement("status");
				$statusContent = $domTree->createTextNode($row['status']);
				$status->appendChild($statusContent);
				$certificate->appendChild($status);

				$beginDate = $domTree->createElement("beginDate");
				/* format the beginDate nicely */
				$timezone = new DateTimeZone($this->person->getTimezone());
				$dt = new DateTime("@" . $row['valid_from']);
				$dt->setTimezone($timezone);
				$valid_from = $dt->format('Y-m-d H:i:s T');
				$beginDateContent = $domTree->createTextNode($valid_from);
				$beginDate->appendChild($beginDateContent);
				$certificate->appendChild($beginDate);

				$endDate = $domTree->createElement("endDate");
				$endDateContent = $domTree->createTextNode($row['valid_untill']);
				$endDate->appendChild($endDateContent);
				$certificate->appendChild($endDate);

				$certificates->appendChild($certificate);
			}
		}

		$domTree->appendChild($certificates);

		if ($domTree->relaxNGValidate("schema/certlist.rng") === FALSE) {
			$msg = "The XML-response the portal built appears to be non-conformant to its schema!\n";
			$this->errorInternal($msg);
		}

		$xmlString = $domTree->saveXML();

		$xmlHash = hash("sha256", $xmlString);
		header("ETag: \"$xmlHash\"");
		echo $xmlString;
		exit(0);
	} /* end processListCerts */

	/**
	 * ship the CSR to the CA and let it sign the request
	 *
	 * @param $csr The CSR that is to be signed
	 */
	public function processSigningRequest($csr)
	{
		/* FIXME: Adapt to the new API once it exists */
		require_once 'csr_lib.php';
		$auth_key = pubkey_hash($csr, TRUE);

		if (!test_content($csr, $auth_key)) {
			$msg = "The CSR you posted appears to be malformed!\n";
			$this->errorBadRequest($msg);
		}

		try {
			/* FIXME: will not work, until e-mail addresses for the cert can
			 * be passed to the signkKey function */
			$this->ca->signKey($auth_key, $csr);
		} catch (ConfusaGenException $cge) {
			$this->errorUncaughtException($cge);
		}

		header("HTTP/1.1 202 Accepted");
		/* FIXME: include the actual status here */
		echo "status=Accepted\n";
		exit(0);
	}
} /* end class CertificateAPI */

$certAPI = new API_Certificates();
$certAPI->processRequest();
?>
