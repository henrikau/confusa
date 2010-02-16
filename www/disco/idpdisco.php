<?php
require_once '../confusa_include.php';
require_once 'content_page.php';
require_once 'framework.php';
require_once 'confusa_gen.php';
require_once 'mdb2_wrapper.php';
require_once 'input.php';
require_once 'logger.php';

/**
 * Class showing an IdP-discovery page tailored to the requirements of
 * Confusa's cross-federated setup. First it is checked, whether a NREN can
 * be deduced from the server-URL and if so, the user is redirected to
 * simplesamlphp's discovery service with a scoped list of that NREN's IdPs
 * as query-parameters.
 *
 * If the NREN can not be deduced, a map is shown on which the user can pick
 * her country. Then the user is forwarded to simplesamlphp's disco-page with
 * a scoped IdP-list of IdPs of the *country* which the user has picked. This
 * distinction is important because some countries (France,...) have more than
 * one NREN.
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
	private $SCOPE_PARAM = "&IDPList[]=";

	function __construct()
	{
		$this->tpl	= new Smarty();
		$this->tpl->template_dir= Config::get_config('install_path').'lib/smarty/templates';
		$this->tpl->compile_dir	= ConfusaConstants::$SMARTY_TEMPLATES_C;
		$this->tpl->config_dir	= Config::get_config('install_path').'lib/smarty/configs';
		$this->tpl->cache_dir	= ConfusaConstants::$SMARTY_CACHE;

		$sspdir = Config::get_config('simplesaml_path');
		require_once $sspdir . 'lib/_autoload.php';
		SimpleSAML_Configuration::setConfigDir($sspdir . '/config');
		$sspConfig = SimpleSAML_Configuration::getInstance();
		$this->discoPath = "https://" . $_SERVER['SERVER_NAME'] . "/" .
		             $sspConfig->getString('baseurlpath') .
		             "module.php/saml/disco.php?" .
		             $_SERVER['QUERY_STRING'];

		$this->translator = new Translator();
		$this->translator->guessBestLanguage(new Person());
	}

	public function pre_process()
	{
		$this->showNRENIdPs(CS::getSessionKey('nren'));
		$this->displayNRENSelection();
	} /* end pre-process */

	/**
	 * Forward the user to the simplesamlphp IdPdisco showing the IdPs of the
	 * NREN associated with the server-URL $url. Use the IdP-scoping via the
	 * dedicated GET parameter for that to limit the number of displayed
	 * IdPs.
	 *
	 * @param $url string The URL which is bound to the NREN, whose IdPs should
	 *                    be shown
	 * @return null
	 */
	private function showNRENIdPs($nrenName)
	{
		$nren = NREN::getNRENByName($nrenName);

		if (empty($nren)) {
			return;
		}

		$scopedIDPList = $nren->getIdPList();

		foreach($scopedIDPList as $key => $idp) {
			$queryString .= $this->SCOPE_PARAM . $idp;
		}

		header("Location: " . $this->discoPath . $queryString);
		exit(0);
	}

	/**
	 * Show a country map with links (for each country) to the simplesamlphp
	 * disco scoped to the IdPs of that country
	 */
	private function displayNRENSelection()
	{
		$query = "SELECT m.idp_url, n.country FROM idp_map m, nrens n
				  WHERE n.nren_id = m.nren_id";

		try {
			$res = MDB2Wrapper::execute($query, null, null);
		} catch (ConfusaGenException $cge) {
			Logger::log_event(LOG_WARN, __FILE__ . " " . __LINE__ . ": [norm] Could not " .
			                  "get the IdP-URLs for the different countries from " .
			                  "the DB. Probably Confusa is misconfigured? " .
			                  $cge->getMessage());
			$this->tpl->assign('error_message',
			                   $this->translator->getTextForTag('l10n_err_idpretrieval', 'disco'));
		}

		if (count($res) > 0) {
			$idpList = array();
			$scopeParam = htmlentities($this->SCOPE_PARAM);

			foreach ($res as $row) {
				$country = strtolower($row['country']);
				$idpList[$country] .= $row['idp_url'] . $scopeParam;
			}
		}

		foreach ($idpList as $country => $nrenIdPScopes) {
			$nrenIdPScopes = $scopeParam . substr($nrenIdPScopes, 0, strlen($nrenIdPScopes) -
			                                                         strlen($scopeParam));
			/* update the value in the list */
			$idpList[$country] = $nrenIdPScopes;
			$this->tpl->assign("scopedIdPs_$country", $nrenIdPScopes);
		}

		$this->translator->decorateTemplate($this->tpl, 'disco');
		$this->tpl->assign('disco_path', htmlentities($this->discoPath));
		$this->tpl->display('disco/idpdisco.tpl');
	}
} /* end class IdPDisco */

$disco = new IdPDisco();
$disco->pre_process();
?>
