<?php
class sspmod_core_Auth_Process_ConfusaAttributeMap extends SimpleSAML_Auth_ProcessingFilter {

     public function __construct($config, $reserved) {
          parent::__construct($config, $reserved);
     }
     public function process(&$request) {
	     if (isset($request['Source']['entityid'])) {
               switch($request['Source']['entityid']) {
	       case "https://idp-test.feide.no":
	       case "https://idp.feide.no":
		       $request['Attributes']['organization'][0] = "feide";
		       break;
               case "https://openidp.feide.no":
		       $request['Attributes']['eduPersonOrgDN'][0]= "o=openidp";
		       $request['Attributes']['nren'][0]	= "uninett";
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
	     $this->fix_entitlement($request);
	     $this->fixSubscriberName($request);
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
               $request['Attributes']['cn'][0] = array(base64_decode($request['Attributes']['cn'][0]));
          if (isset($request['Attributes']['mail'][0]))
               $request['Attributes']['mail'][0] = array(base64_decode($request['Attributes']['mail'][0]));

     }

     private function fix_entitlement(&$request)
     {
	     if (!isset($request['Attributes']['eduPersonEntitlement'][0])) {
		     $request['Attributes']['eduPersonEntitlement'][0] = "confusaAdmin";
	     }
     }

     /**
      * fixSubscriberName() - parse the ePODN to find the subscriber name
      *
      * We expect the IdP to export the eduPersonOrgDN (ePODN) as described in
      *
      *			http://rnd.feide.no/attribute/edupersonorgdn
      *
      * and the expected form is:
      *
      *			o=Hogwarts, dc=hsww, dc=wiz
      *
      * We want the 'highest' attribute, i.e. the attribute first in the chain
      * as this normally describes the institution.
      */
     private function fixSubscriberName(&$request)
     {
	     /* Is ePODN set? */
	     if (!isset($request['Attributes']['eduPersonOrgDN'][0]) || $request['Attributes']['eduPersonOrgDN'][0] == "") {
		     echo "<h3>Error!</h3>\n";
		     echo "Your IdP does not export all required attributes. The attribute ";
		     echo "<a href=\"http://rnd.feide.no/attribute/edupersonorgdn\">eduPersonOrgDN</a> is not set!<br /><br />\n";
		     echo "Please notify your local IT-support about this problem.\n";
		     exit(1);
	     }
	     $org = explode(",", $request['Attributes']['eduPersonOrgDN'][0]);
	     if (is_array($org)) {
		     $org = explode("=", $org[0]);
		     if (is_array($org)) {
			     $request['Attributes']['organization'][0] = $org[1];
		     } else {
			     $request['Attributes']['organization'][0] = $org;
		     }
	     } else {
		     $request['Attributes']['organization'][0] = "__error__";
	     }
	     $request['Attributes']['organization'][0] = strtolower($request['Attributes']['organization'][0]);
     } /* end fixSusbscriberName() */
}
?>
