<?php
require_once 'CSR_PKCS10.php';
require_once 'file.php';
require_once 'Input.php';

/**
 * Class for handling uploads of CSRs
 * @author tzangerl
 *
 */
class CSRUpload
{
	/**
	 * get a CSR from a file upload
	 * @param $csrName the name of the file in the $_FILES array
	 * @param $testBlacklist check whether the openssl pubkey is suffering
	 *                       from the known Debian vulnerability
	 * @return CSR_PKCS10 object containing the uploaded CSR if successful
	 * @throws FileException if the file transfer was erroneous/incomplete
	 */
	public static function receiveUploadedCSR($csrName, $testBlacklist = false)
	{
		/* bubble up exception from here */
		CSRUpload::testFile($csrName);

		/* 'tmpFile' is PHP-generated */
		$fname = $_FILES[$csrName]['tmp_name'];

		$fsize = filesize($fname);
		$fd	= fopen($fname,'r');
		$content= Input::sanitizeBase64(fread($fd, $fsize));
		fclose($fd);

		if ($testBlacklist === true) {
			CSRUpload::testBlacklist($content);
		}

		return new CSR_PKCS10($content);
	}

	/**
	 * receivePastedCSR() handle a CSR pasted through an input-field
	 *
	 * @param String $csr_var_name String containing the CSR
	 * @param Boolena $testBlacklist true if the CSR should be matched to the
	 * openssl-vulnkey
	 *
	 * @return CSR_PKCS10|null
	 * @throws ConfusaGenException if fthe CSR is malformed, blacklisted or
	 *					otherwise invalid.
	 */
	public static function receivePastedCSR($csr_var_name, $testBlacklist = false)
	{
		if (!isset($_POST) || !array_key_exists($csr_var_name, $_POST)) {
			throw new ConfusaGenException("csr not found in $_POST!");
		}
		$csr_content = Input::sanitizeBase64($_POST[$csr_var_name]);
		if ($testBlacklist) {
			CSRUpload::testBlacklist($csr_content);
		}
		return new CSR_PKCS10($csr_content);
	}

	/**
	 * testFile() see if any known error-conditions are set for the file
	 *
	 * @param void
	 * @return boolean true when <b>no</b> errors are detected.
	 */
	static function testFile($fname)
	{
		if (isset($_FILES[$fname])) {
			switch($_FILES[$fname]['error']) {
			case UPLOAD_ERR_OK:
				return true;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new FileException("Size of file exceeds maximum " .
				                        "allowed filesize.");
			case UPLOAD_ERR_PARTIAL:
				throw new FileException("Upload did not finish properly, " .
				                        "incomplete file. Try again");
			case UPLOAD_ERR_NO_FILE:
				throw new FileException("No file given to upload-handler!");
			default:
				throw new FileException("Unknown error condition!");
			}
			/* if nothing bad detected, assume it's OK */
			return true;
		}
	}

	/**
	 * Test whether the CSR in $content contains a public key that is
	 * blacklisted (due to the Debian prime number generator flaw).
	 *
	 * If the key is blacklisted, this method will throw an exception
	 * @param $content String containing CSR to be tested
	 * @throws ConfusaGenException if key is blacklisted
	 */
	static function testBlacklist($content)
	{
		$shellContent = Input::sanitizeBase64(escapeshellarg($content));
		$fp = popen("echo $shellContent | openssl-vulnkey -", "r");
		if (!$fp) {
			Logger::log_event(LOG_ALERT, __CLASS__ . "::testBlacklist()",
							  " Could not open process file-pointer in order to test for blacklisted CSR!");
			/* if we cannot open openssl-vulnkey, we must assume that all uploaded
			 * keys are blacklisted */
			/* FIXME: add l10n */
			throw new ConfusaGenException("Could not verify CSR against blacklist!");
		}
		$res = fread($fp, 1024);
		fclose($fp);
		if (stripos($res, "not blacklisted", 0) === 0) {
			return;
		} else if (stripos($res, "COMPROMISED", 0) === 0) {
			throw new ConfusaGenException("Key is blacklisted!");
		}
		Logger::log_event(LOG_DEBUG, __CLASS__ . "::testBlacklist()",
						  " Unknown return ($res) value from shell");
	}
}
	?>