<?php
/* Henrik Austad, 2009
 *
 * Part of Confusa, GPLv3 applies.
 *
 * Simple class for finding the country the user comes from.
 * By mapping the idp to country, we have a very easy and secure way of
 * determining the country the user comes from.
 *
 * One problem might be OpenID, but for all the national federations, this
 * should be accurate enough.
 */
class sspmod_core_Auth_Process_CountryMap extends SimpleSAML_Auth_ProcessingFilter {
     private $known_idps = array();

     public function __construct($config, $reserved) {
          parent::__construct($config, $reserved);
          /* set the known idps */
          $this->known_idps = array('https://openidp.feide.no' => 'NO',
                                    'max.feide.no' => 'NO',
                                    'edugain.showcase.surfnet.nl' => 'NL',
                                    'https://testidp.wayf.dk' => 'DK',
                                    'https://betawayf.wayf.dk' => 'DK',
                                    'https://aitta2.funet.fi/idp/shibboleth' => 'FI'
               );
     }

     public function process(&$request) {
          $idp = $request['Source']['entityid'];
          $request['Attributes']['country'] = array($this->known_idps[$idp]);
     }
}
?>
