<?php
require_once '../confusa_include.php';
require_once 'NREN_Handler.php';
require_once 'Content_Page.php';
require_once 'Framework.php';
require_once 'confusa_gen.php';
require_once 'MDB2Wrapper.php';
require_once 'Input.php';
require_once 'Logger.php';

/**
 * IdPDisco - tailor the IdP-discovery page.
 *
 * It does this in several, discrete steps:
 * 1) Can the NREN be deduced from the server-URL?
 *	-> Yes: user directed to SimpleSAMLphp's discovery service scoped to the
 *		list of that particular NRENs IdPs
 * 2) Show a map of Europe where the user can pick the country. Confusa then
 *    turns into the same mode as in step 1, showing a scoped list of all IdPs
 *    registred for the given country.
 *
 *    This distinction is important because some countries have more than one
 *    NREN.
 *
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 * @since  v0.6-rc0
 */
class IdPDisco
{
	private $tpl;
	private $discoPath;
	private $translator;

	/* GET parameter used in simplesamlphp to scope the list of IdPs */
	private $SCOPE_PARAM = "IDPList[]";
	/* GET parameter used in simplesamlphp to skip disco entirely and proceed to
	 * the passed IdP. Good to have if there is only *one* for a country */
	private $IDP_PARAM = "&idpentityid=";

	function __construct()
	{
		/* SimpleSAMLphp include and initial configuration */
		$sspdir		 = Config::get_config('simplesaml_path');
		require_once $sspdir . 'lib/_autoload.php';
		SimpleSAML_Configuration::setConfigDir($sspdir . '/config');
		$sspConfig	 = SimpleSAML_Configuration::getInstance();
		$this->discoPath = "https://" . $_SERVER['SERVER_NAME'] . "/" .
			$sspConfig->getString('baseurlpath') .
			"module.php/saml/disco.php?" .
			$_SERVER['QUERY_STRING'];


		$this->tpl		= new Smarty();
		$this->tpl->template_dir= Config::get_config('install_path').'templates';
		$this->tpl->compile_dir	= ConfusaConstants::$SMARTY_TEMPLATES_C;
		$this->tpl->cache_dir	= ConfusaConstants::$SMARTY_CACHE;
		$this->translator = new Translator();
		$this->translator->guessBestLanguage(new Person());

		$this->showNRENIdPs($_SERVER['SERVER_NAME']);
		$this->displayNRENSelection();
	} /* end __construct */

	/**
	 * Forward the user to the simplesamlphp IdPdisco showing the IdPs of the
	 * NREN associated with the server-URL $url. Use the IdP-scoping via the
	 * dedicated GET parameter for that to limit the number of displayed
	 * IdPs, or the NREN-defined WAYF-URL if it exists. Use the session-key
	 * for detection of the user's NREN.
	 *
	 * @param $url string the URL of the NREN whose IdPs should be shown
	 *
	 * @return null
	 * @access private
	 */
	private function showNRENIdPs($url)
	{
		$nren = NREN_Handler::getNREN($url, 1);

		/* No NREN with the given URL found in the table, cannot set
		 * the scoping */
		if (empty($nren)) {
			return;
		}

		/* if the NREN has its own WAYF, redirect to WAYF, set the
		 * return-address and stop the rendering. */
		$wayf = $nren->getWAYFURL();

		/* the WAYF URL must contain a protocol part, otherwise it will be
		 * interpreted as relative to the disco */
		if (isset($wayf)) {
			if (strpos($wayf, "://") !== FALSE) {
				header("Location: " . $wayf . "?" . $_SERVER['QUERY_STRING']);
				exit(0);
			} else {
				Logger::logEvent(LOG_NOTICE, __CLASS__, __METHOD__,
				                 "Can not use NREN WAYF-URL $wayf as it " .
				                 "does not contain a protocol!");
			}
		}

		$scopedIDPList	= $nren->getIdPList();
		$queryString	= "";
		switch (count($scopedIDPList)) {
		case 0:
			Logger::log_event(LOG_ALERT, "No IdP found for NREN " . $nren->getName() .
					  " disco-selection will probably fail..");
			break;
		case 1:
			$queryString = "&" . $this->SCOPE_PARAM . $scopedIDPList[0];
			break;
		default:
			foreach($scopedIDPList as $key => $idp) {
				$queryString .= "&" . $this->SCOPE_PARAM . $idp;
			}
			break;
		}
		header("Location: " . $this->discoPath . $queryString);
		exit(0);
	} /* end showNRENIdPs() */

	/**
	 * displayNRENSelection() Display a list of all idp_urls from all NRENs
	 *
	 * This function shall return a list of all available IdP-URLs registred
	 * in the database so that it can be listed in the idpdisco-page.
	 *
	 * @param void
	 * @return void
	 * @access private
	 */
	private function displayNRENSelection()
	{
		$query = "SELECT m.idp_url, n.country FROM idp_map m, nrens n " .
			"WHERE n.nren_id = m.nren_id";

		try {
			$res = MDB2Wrapper::execute($query, null, null);
		} catch (ConfusaGenException $cge) {
			Logger::log_event(LOG_WARNING, __FILE__ . " " . __LINE__ .
					  ": [norm] Could not " .
			                  "get the IdP-URLs for the different countries from " .
			                  "the DB. Probably Confusa is misconfigured? " .
			                  $cge->getMessage());
			$this->tpl->assign('error_message',
					   "Error while trying to retrieve the IdPs for the different NRENs");
		}

		if (count($res) > 0) {
			$idpList	= array();
			$scopeParam	= htmlentities($this->SCOPE_PARAM);
			$idpParam	= htmlentities($this->IDP_PARAM);

			foreach ($res as $row) {
				$country = strtolower($row['country']);
				if (!isset($idpList[$country])) {
					$idpList[$country] = "";
				}
				$idpList[$country][] = $row['idp_url'];
			}
		}

		foreach ($idpList as $country => $nrenIdPScopes) {

			if (count($nrenIdPScopes) > 1) {

				$this->tpl->assign("scopeMethod_$country", "post");
				$this->tpl->assign("scopeKey_$country", $this->SCOPE_PARAM);
				$this->tpl->assign("scopedIdPs_$country", $nrenIdPScopes);

			} else if (count($nrenIdPScopes) == 1) {

				$this->tpl->assign("scopeMethod_$country", "get");
				$this->tpl->assign("scopeKey_$country", $this->IDP_PARAM);
				$this->tpl->assign("scopedIdPs_$country", $nrenIdPScopes[0]);

			} else {

				continue;

			}

			/* update the value in the list */
			$idpList[$country] = $nrenIdPScopes;
			$this->tpl->assign("scopedIdPs_$country", $nrenIdPScopes);
		}

		$this->translator->decorateTemplate($this->tpl, 'disco');
		$this->tpl->assign('disco_path', htmlentities($this->discoPath));
		$this->tpl->display('disco/idpdisco.tpl');
	} /* end displayNRENSelection() */
} /* end class IdPDisco */

$disco = new IdPDisco();
?>
