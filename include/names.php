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
     $known_idps = array('NO' => 'max.feide.no',
                         'NL' => 'edugain.showcase.surfnet.nl',
                         'DK' => 'wayf.wayf.dk');
     $res = array_search($idpentityid, $known_idps);
     if ($res) {
          $attributes['country'] = array($res);
     }
     else {
          /* did not find in predefined array. Try to recover gracefully */
          echo __FILE__.":".__LINE__." Did not find $idpentityid in \$known_idps<br>\n";
          $lastdot = strrpos($idpentityid, ".");
          $attributes['country'] = array(strtoupper(substr($idpentityid, $lastdot+1)));
     }

     /* fix shortnames -> add to feide-compatible */
     if (isset($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0])) {
          $attributes['eduPersonPrincipalName'] = array($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0]);
          $attributes['cn']                     = array($attributes['urn:mace:dir:attribute-def:cn'][0]);
          $attributes['mail']                   = array($attributes['urn:mace:dir:attribute-def:mail'][0]);
     }
}


?>