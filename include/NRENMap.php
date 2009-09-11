<?php
  /* Henrik Austad, 2009
   *
   * Part of Confusa, GPLv3 applies.
   *
   * This maps an IdP to an NREN. As an IdP must belong to one and only one NREN,
   * but one NREN may consist of several IdPs, we must map this the same way we
   * map the Countries.
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
					  'https://testidp.wayf.dk'		=> 'wayf',
					  'https://betawayf.wayf.dk'		=> 'wayf',
					  'https://aitta2.funet.fi/idp/shibboleth'	=> 'haka'
			);
	}

	public function process(&$request) {
		$idp = $request['Source']['entityid'];
		$request['Attributes']['nren'] = array($this->known_nrens[$idp]);
	}
  }
?>
