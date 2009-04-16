<?php
class sspmod_core_Auth_Process_ConfusaAttributeMap extends SimpleSAML_Auth_ProcessingFilter {

     public function __construct($config, $reserved) {
          parent::__construct($config, $reserved);
     }
     public function process(&$request) {
          if (isset($request['Attributes']['urn:mace:dir:attribute-def:eduPersonPrincipalName']))
               $this->fix_surfnet($request);

          if (isset($request['Attributes']['urn:oid:1.3.6.1.4.1.5923.1.1.1.6']))
               $this->fix_haka($request);
          if (isset($request['Attributes']['IdP'])) {
               $idp = $request['Attributes']['IdP'][0];
               switch($idp) {
               case "max.feide.no":
                    break;
               case "edugain.showcase.surfnet.nl":
                    $this->fix_surfnet($request);
                    break;
               case "https://testidp.wayf.dk":
               case "https://betawayf.wayf.dk":
                    $this->fix_wayf($request);
                    break;
               case "https://aitta2.funet.fi/idp/shibboleth":
                    $this->fix_haka($request);
                    break;
               default:
                    echo "Unknown IdP - $idp<BR>\n";
               }
          }
     }

     private function fix_surfnet(&$request) {

          if (isset($request['Attributes']['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0]))
               $request['Attributes']['eduPersonPrincipalName'] = $request['Attributes']['urn:mace:dir:attribute-def:eduPersonPrincipalName'];

          if (isset($request['Attributes']['urn:mace:dir:attribute-def:cn'][0]))
               $request['Attributes']['cn'] = $request['Attributes']['urn:mace:dir:attribute-def:cn'];

          if (isset($request['Attributes']['urn:mace:dir:attribute-def:mail'][0]))
               $request['Attributes']['mail'] = $request['Attributes']['urn:mace:dir:attribute-def:mail'];
     }

     private function fix_haka(&$request) {
          if (isset($request['Attributes']['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'][0]))
               $request['Attributes']['eduPersonPrincipalName'] = $request['Attributes']['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'];
          if (isset($request['Attributes']['urn:oid:2.5.4.3'][0]))
               $request['Attributes']['cn'] = $request['Attributes']['urn:oid:2.5.4.3'];
          if (isset($request['Attributes']['urn:oid:0.9.2342.19200300.100.1.3'][0]))
               $request['Attributes']['mail'] = $request['Attributes']['urn:oid:0.9.2342.19200300.100.1.3'];
     }

     private function fix_wayf(&$request) {
          if (isset($request['Attributes']['eduPersonPrincipalName'][0]))
               $request['Attributes']['eduPersonPrincipalName'][0] = array(base64_decode($request['Attributes']['eduPersonPrincipalName'][0]));
          if (isset($request['Attributes']['cn'][0]))
               $request['Attributes']['mail'][0] = array(base64_decode($request['Attributes']['cn'][0]));
          if (isset($request['Attributes']['mail'][0]))
               $request['Attributes']['mail'][0] = array(base64_decode($request['Attributes']['mail'][0]));
     }
}
?>
