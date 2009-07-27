<?php
echo "<HTML>\n";
echo "   <HEAD><TITLE>Confusa Error Messaging Service</TITLE></HEAD>\n";
echo "   <BODY>\n";
echo "      <CENTER>\n";
echo "         <H3>IP address notification</H3>\n";
echo "         The address of the machine uploading a CSR is recorded.\n";
echo "         If, for any reason, the address of your current machine differs from that, a notification";
echo "         is displayed (it is colored in red). <BR />\n";
echo "         <BR />\n";
echo "         This may, or may not, mean anything, but you should be aware of this. <BR />\n";
echo "         <BR /><BR />\n";
echo "         Your current IP is " . $_SERVER['REMOTE_ADDR'] . "<BR />\n";
echo "      </CENTER>\n";
echo "   </BODY>\n";
echo "</HTML\n";

?>

