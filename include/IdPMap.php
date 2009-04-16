<?php
/* Henrik Austad, 2009
 *
 * Part of Confusa, GPLv3 applies.
 * Add IdP to list of Attributes.
 */
class sspmod_core_Auth_Process_IdPMap extends SimpleSAML_Auth_ProcessingFilter {
     public function __construct($config, $reserved) {
          parent::__construct($config, $reserved);
     }
     public function process(&$request) {
          $request['Attributes']['IdP'] = array($request['Source']['entityid']);
     }
}
?>
