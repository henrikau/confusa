<?php
  /* names.php
   *
   * This file is the filter we apply to simpleSAMLphp to change the attributes
   * into unified form (to simplify the code in confusa_auth.php).
   *
   *
   */
function attributealter_names(&$attributes, $spentityid = null, $idpentityid = null)
{
     /* for the in-array search to work properly, the idpentityid must be the
      * content, and we use that to retrieve the key - namely the key. This
      * means that we can only have one idp pr country, as similar keys will
      * overwrite previous entries.
      */
     $known_idps = array('max.feide.no' => 'NO',
                         'edugain.showcase.surfnet.nl' => 'DL',
                         'https://testidp.wayf.dk' => 'DK',
                         'https://betawayf.wayf.dk' => 'DK',
                         'https://aitta2.funet.fi/idp/shibboleth' => 'FI'
                         );

     $attributes['country'] = array($known_idps[$idpentityid]);
     $attributes['orgname'] = array('Nordugrid');
     $attributes['orgunitname'] = array('Nordugrid');

     if (!$attributes['country'][0]) {
          /* did not find in predefined array. Try to recover gracefully */
          echo __FILE__.":".__LINE__." Did not find $idpentityid in \$known_idps<BR>\n";
          echo __FILE__.":".__LINE__." Contact the site administrator with this message<BR>\n";
          $lastdot = strrpos($idpentityid, ".");
          $attributes['country'] = array(strtoupper(substr($idpentityid, $lastdot+1)));
          echo __FILE__.":".__LINE__." Setting ". $attributes['country'][0] . " as country<BR>\n";
     }

     /* Fix shortnames, make attributes compatible with Feide/confusa */
     /* SurfNET */
     if (isset($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0])) {
          $attributes['eduPersonPrincipalName'] = array($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0]);
          $attributes['cn']                     = array($attributes['urn:mace:dir:attribute-def:cn'][0]);
          $attributes['mail']                   = array($attributes['urn:mace:dir:attribute-def:mail'][0]);
     }
     /* HAKA */
     else if (isset($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'])) {
          $attributes['eduPersonPrincipalName'] = array($attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'][0]);
          $attributes['cn']                     = array($attributes['urn:oid:2.5.4.3'][0]);
          $attributes['mail']                   = array($attributes['urn:oid:0.9.2342.19200300.100.1.3'][0]);
     }

     /* WAYF, QA base64 encode the attributes for transport */
     else if ($idpentityid === 'https://betawayf.wayf.dk') {
          $attributes['eduPersonPrincipalName'] = array(base64_decode($attributes[eduPersonPrincipalName][0]));
          $attributes['mail']                   = array(base64_decode($attributes['mail'][0]));
     }
/*      echo "<pre>\n"; */
/*      print_r($attributes); */
/*      echo "</pre>\n"; */
}
?>
