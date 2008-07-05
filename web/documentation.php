<?php
include_once('framework.php');
$fw = new Framework('doc_func');
$fw->render_page();


function doc_func($person)
{
  echo "<H3 class=\"info\">Documentation for Confusa</H3>\n";
  echo "The <A HREF=\"graphics/system_description.jpg\">whole system</A>";
  echo "from the user's perspective<BR>\n";
  echo "The <A HREF=\"graphics/system_decomposition.jpg\">state of the system</A>";
  echo "(what's implemented, what's planned and what's planned, but unstarted. <BR>\n";
  echo "Finally, class-diagram: <A HREF=\"graphics/slcsweb_class_diagram.jpg\">here</A><BR>\n";
}
?>
