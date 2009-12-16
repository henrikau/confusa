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
	download($file, $filename, "application/force-download");
}

/**
 * download a certificate - set the header flags accordingly
 * Note that this is for installation in browsers where the certificate was
 * generated with keygen
 *
 * @param $cert_content The content of the certificate
 * @param $filename The filename with which the certificate will be installed
 */
function download_certificate($cert_content, $filename)
{
	download($cert_content, $filname, "application/x-x509-user-cert");
}


/**
 * download() generic file-download
 *
 * This function makes it easy to download a variety of files without
 * re-implementing other than the Application-header.
 */
function download($content, $filename, $type_header)
{
	header("Content-Type: " . $type_header);
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header('Content-Transfer-Encoding: binary');
	header('Accept-Ranges: bytes');
	header('Content-Length: ' . strlen($content));

	// IE fix (for HTTPS only)
	header('Cache-Control: private');
	header('Pragma: private');
	echo $content;
}

?>
