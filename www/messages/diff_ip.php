<?php
echo "<html>\n";
echo "   <head><title>Confusa Error Messaging Service</title></head>\n";
echo "   <body>\n";
echo "      <center>\n";
echo "         <h3>IP address notification</h3>\n";
echo "         The address of the machine uploading a CSR is recorded.\n";
echo "         If, for any reason, the address of your current machine differs from that, a notification";
echo "         is displayed (it is colored in red). <br />\n";
echo "         <br />\n";
echo "         This may, or may not, mean anything, but you should be aware of this. <br />\n";
echo "         <br /><br />\n";
echo "         Your current IP is " . $_SERVER['REMOTE_ADDR'] . "<br />\n";
echo "      </center>\n";
echo "   </body>\n";
echo "</html>\n";
?>