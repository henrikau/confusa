<?php
/* download_file()
 *
 * Creates a download-page to the user that triggers the download-dialog in any
 * normally behaved browser.
 *
 * Note: The output must start *before* any other headers are rewritten, and
 * must not be called if any other part relies on header-rewriting.
 *
 * Author: Henrik Austad <henrik.austad@uninett.no>
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
