<?php
function attributealter_names(&$attributes, $spentityid = null, $idpentityid = null)
{
     /* $attributes['country'] = 'NO'; */
     /* get idp */
     $lastdot = strrpos($idpentityid, ".");
     $attributes['country'] = array(strtoupper(substr($idpentityid, $lastdot+1)));

     /* fix shortnames -> add to feide-compatible */
     if (isset($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0])) {
          $attributes['eduPersonPrincipalName'] = array($attributes['urn:mace:dir:attribute-def:eduPersonPrincipalName'][0]);
          $attributes['cn']                     = array($attributes['urn:mace:dir:attribute-def:cn'][0]);
          $attributes['mail']                   = array($attributes['urn:mace:dir:attribute-def:mail'][0]);
     }
}


?>