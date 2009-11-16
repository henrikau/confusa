<?php
  /**
   * CountryMap - map the IdP to a country.
   *
   * This map will use the IdP-identifier to retrieve the country. As an IdP can
   * only belong to a single NREN, and each NREN can only belong to a single
   * country, we can match the IdP to the country exactly.
   *
   * This file must be added to simplesamlphp/config/config.php::authproc.sp
   * (see INSTALL for instructions) and symlinked to
   *	<simplesamlphp>/modules/core/lib/Auth/Process/
   *
   * @author	Henrik Austad <henrik.austad@uninett.no>
   */
class sspmod_core_Auth_Process_CountryMap extends SimpleSAML_Auth_ProcessingFilter {
	private $known_idps = array();

	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);
		/* set the known idps.
		 * These names will be matched against $request['Source'][entityid'],
		 * which is the array-key in simpelsamlphp's saml20-idp-remote.php
		 */
		$this->known_idps = array('https://openidp.feide.no'			=> 'NO',
					  'https://idp-test.feide.no'			=> 'NO',
					  'https://idp.feide.no'			=> 'NO',

					  'edugain.showcase.surfnet.nl'		=> 'NL',
					  'https://fedex.terena.org'			=> 'NL',

					  'https://testidp.wayf.dk'			=> 'DK',
					  'https://betawayf.wayf.dk'			=> 'DK',

					  'https://aitta2.funet.fi/idp/shibboleth'	=> 'FI'
			);
	}

	public function process(&$request) {
		$idp = $request['Source']['entityid'];
		if (is_null($request['Attributes']['idp'])) {
			$request['Attributes']['idp'] = array($idp);
		}
		$request['Attributes']['country'] = array($this->known_idps[$idp]);
	}
  }
?>
