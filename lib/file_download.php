<?php
/* download_file()
 *
 * Creates a download to the user 
 */
function download_file($file, $filename)
{
     header('Content-Type: application/force-download');
     header('Content-Disposition: attachment; filename="'.$filename.'"');
     header('Content-Transfer-Encoding: binary');
     header('Accept-Ranges: bytes');
     header('Content-Length: ' . strlen($file));

     /* turn of caching */
     header("Cache-control: no-cache, must-revalidate");
     header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
     echo $file;
}
?>
