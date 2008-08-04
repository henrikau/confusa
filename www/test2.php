<?php
include_once('framework.php');
require_once('SimpleSAML/Utilities.php');
$fw = new Framework('show_form');
$fw->render_page();


function show_form($person) {
     /* read from metadatafiles */
     $config = SimpleSAML_Configuration::getInstance();
     $metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
     $session = SimpleSAML_Session::getInstance();
     $spmetadata = $metadata->getMetaDataCurrent('saml20-sp-hosted');

     $feide = 'urn:mace:feide.no:services:no.uninett.slcsweb';
     $surfnet='urn:mace:showcase.surfnet.nl:services:no.uninett.slcsweb';

     $baseurl="https://slcstest.uninett.no/slcsweb/saml2/sp/initSSO.php?RelayState=https%3A%2F%2Fslcstest.uninett.no%2Fslcsweb%2Findex.php%3Fstart_login%3Dyes";

     print_r($nameidformat);
?>
          Login in using Feide:
<FORM METHOD="get" ACTION="https://slcstest.uninett.no/slcsweb/saml2/sp/idpdisco.php">
<INPUT TYPE="hidden" NAME="entityID" VALUE="<?php echo $feide; ?>" />
<INPUT TYPE="hidden" NAME="return" VALUE="<?php echo $baseurl ?>"/>
<INPUT TYPE="hidden" NAME="returnIDParam" VALUE="idpentityid" />
<INPUT ID="preferredidp" TYPE="submit" NAME="idp_max.feide.no" VALUE="Feide" />
</FORM>
Log in using Surfnet
<FORM METHOD="get" ACTION="https://slcstest.uninett.no/slcsweb/saml2/sp/idpdisco.php">
<INPUT TYPE="hidden" NAME="entityID" VALUE="<?php echo $surfnet; ?>" />
<INPUT TYPE="hidden" NAME="return" VALUE="<?php echo $baseurl ?>"/>
<INPUT TYPE="hidden" NAME="returnIDParam" VALUE="idpentityid" />
<INPUT ID="preferredidp" TYPE="submit" NAME="idp_edugain.showcase.surfnet.nl" VALUE="Surfnet" />
</FORM>

<?php
}
?>
