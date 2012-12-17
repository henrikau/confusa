<?php
require_once 'confusa_include.php';
require_once 'Content_Page.php';
require_once 'MDB2Wrapper.php';
include_once 'Framework.php';
include_once 'Logger.php';


final class CP_IdP_Select extends Content_Page
{
	private $mapMode;
	private $cidp;
	private $discoPath;
	function __construct()
	{
		parent::__construct("IdP Select", false, "disco");
		$this->mapMode = true;
	}

	public function pre_process($person)
	{
		parent::pre_process($person);
		$auth = AuthHandler::getAuthManager($this->person);
		$this->discoPath = $auth->getDiscoPath();

		/*
		 * Handle country AuthN redirect. Both can redirect, if they don't, show
		 * the map.
		 */
		$nren = NREN_Handler::getNREN($_SERVER['SERVER_NAME']);
		if (!empty($nren)) {
			$this->redirectToWAYF($nren);
			$this->forwardToDisco($nren);
		}

		/* if not redirected, continue  */
		if (array_key_exists('country', $_GET)) {
			$this->selected_country = htmlentities($_GET['country']);
			$nren = NREN_Handler::getNREN($url, 1);
			echo "redirecting to idp-part for " . $this->selected_country .
				", stopping rendering of this page now\n"; exit(0);
		}

		/* textual view? */
		if (array_key_exists('textual_view', $_GET)) {
			if ($_GET['textual_view'] === "yes") {
				$this->mapMode = false;
			}
		} else {
			/* ok, show map */
			$this->tpl->assign('extraScripts', array('js/jquery-1.6.1.min.js',
													 'js/jquery-jvectormap-1.1.1.min.js',
													 'js/jquery-jvectormap-europe-mill-en.js'));
		}
	}

	function process()
	{
		if ($this->person->isAuth()) {
			Framework::error_output("You are already AuthN, perhaps you want to log out?");
		}
		$select_tpl = "";
		$this->tpl->assign('disco_path', $this->discoPath);
		$this->tpl->assign('configuredCountries', $this->getCountries());
		if ($this->mapMode) {

			/* FIXME: add map-name an config-option */
			$this->tpl->assign('idp_map_name', 'europe_mill_en');
			$select_tpl = "idp_select.tpl";
			$this->tpl->assign('idplist', $this->getCountriesIdP());

		}
		$this->tpl->assign('content', $this->tpl->fetch($select_tpl));
	} /* end process() */

	/**
	 * If the url of the server has a registred wayf-service, redirect to this
	 */
	private function redirectToWAYF($nren)
	{
		$wayf = $nren->getWAYFURL();
		if (isset($wayf)) {

			/* redirect to wayf */
			if (strpos($wayf, "://") !== FALSE) {
				header("Location: " . $wayf . "?" . $_SERVER['QUERY_STRING']);
				exit(0);
			} else {
				Logger::log_event(LOG_NOTICE, "Cannot use NREN WAYF-URL $wayf as it " .
								  "does not contain a protocol, url must be configured properly");
			}
		}
	}

	private function forwardToDisco()
	{
		$scopedIDPList	= $nren->getIdPList();
		$queryString	= "";
		switch (count($scopedIDPList)) {
		case 0:
			Logger::log_event(LOG_ALERT, "No IdP found for NREN " . $nren->getName() .
					  " disco-selection will probably fail..");
			break;
		case 1:
			$queryString = "&" . $this->SCOPE_PARAM . "=" . $scopedIDPList[0];
			break;
		default:
			foreach($scopedIDPList as $key => $idp) {
				$queryString .= "&" . $this->SCOPE_PARAM . "=" . $idp;
			}
			break;
		}
		header("Location: " . $this->discoPath . $queryString);
		exit(0);
	} /* end forwardToDisco() */

	private function getCountries()
	{
		$countries = array();
		$list = $this->getCountriesIdP();

		foreach($list as $key => $n) {
			$countries[] = $n['country'];
		}
		return $countries;
	} /* end getCountries */

	/** getCountriesIdP() return all countries and IdP present in the database
	 *
	 * @params void
	 * @return array list of available countries with corresponding IdP(s)
	 * @access private
	 */
	private function getCountriesIdP()
	{
		if (isset($this->cidp)) {
			return $this->cidp;
		}
		try {
			$res = MDB2Wrapper::execute("SELECT idp_url, country, name FROM nrens n " .
										"LEFT JOIN idp_map im ON n.nren_id = im.nren_id",
										NULL,
										NULL);
		} catch (ConfusaGenException $cge) {
			Logger::log_event(LOG_WARNING, "Could not get IdP-URLs from the database, ".
							  "make sure DB-connection is properly configured\n");
			Framework::error_output($this->translateTag('l10n_err_db_select', 'disco'));
			return array();
		}
		$this->cidp = array();
		foreach ($res as $key => $value) {
			if (!isset($this->cidp[$value['country']])) {
				$this->cidp[$value['country']] = array();
			}
			$this->cidp[$value['country']][] = $value['idp_url'];
		}
		return $res;;
	} /* end getCountriesIdP() */

}

$fw  = new Framework(new CP_IdP_Select());
$fw->start();
unset($fw);
