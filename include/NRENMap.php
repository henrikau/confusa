<?php
  /**
   * NRENMap - map a given IdP to an NREN.
   *
   * Since any given IdP can only belong to a *single* NREN, this is a
   * many-to-one mapping.
   *
   * @license LGPLv3
   * @author	Henrik Austad <henrik.austad@uninett.no>
   */
class sspmod_core_Auth_Process_NRENMap extends SimpleSAML_Auth_ProcessingFilter {
	private $known_nrens = array();

	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);
		/* set the known idps */
		$this->known_nrens = array('https://openidp.feide.no'		=> 'uninett',
					   'https://idp-test.feide.no'		=> 'uninett',
					   'https://idp.feide.no'		=> 'uninett',

					   'edugain.showcase.surfnet.nl'		=> 'surfnet',
					   'https://fedex.terena.org'		=> 'surfnet',

					   'https://testidp.wayf.dk'		=> 'wayf',
					   'https://betawayf.wayf.dk'		=> 'wayf',

					   'https://aitta2.funet.fi/idp/shibboleth'	=> 'haka',
			);
	}

	public function process(&$request) {
		$idp = $request['Source']['entityid'];
		$request['Attributes']['nren'] = array($this->known_nrens[$idp]);
	}
  }
?>
