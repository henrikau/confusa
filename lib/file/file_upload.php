<?php
/* FileUpload
 *
 * Class for handling upload of files.
 *
 * parameters:
 *	$fname:		the name of the $_FILES[..] filed, ie the name of the <FORM> file.
 *
 *	$mem_read:	wether or not to read the content from file, or handle it
 *			strictly via file I/O (useful for *large* files or at systems
 *			with restricted memory).
 *
 *	$file_test_func:Supplied function for testing the content of the file. This function will
 *			either be given the content of the file, or just an opened filediscriptor
 *			and must handle this accordingly (i.e. the use of FileUpload
 *			must know wether or not the file shall be read from memory or not
 *
 *			If not set (null), this step is bypassed
 *
 * Author: Henrik Austad <henrik.austad@uninett.no>
 *
 */
include_once 'config.php';

class FileUpload {
  private $open_file;		/* the field of $_FILES which we want to read */
  private $filename;
  private $keep;		/* if the file should be kept after object termination or not */
  private $fcont;		/* the content of the file (either read in from memory or
				 * as a filedescriptor. */
  private $mem;			/* if the content should be read into memory or kept as a file
				   descriptor*/
  private $file_ok;		/* if the file's content is ok or not */
  private $parsed;		/* if the file has already been read and tested */
  private $test_func;

  function __construct($fname, $mem_read, $parse, $file_test_func) {
    $this->open_file	= $fname;
    $this->filename	= $_FILES[$this->open_file]['tmp_name'];
    $this->keep		= false;
    $this->fcont	= null;
    $this->mem		= false;
    $this->parsed	= false;
    $this->file_ok	= false;

    if ($mem_read === true)
      $this->mem = true;

    $this->test_func = 'trivial_test';
    if (isset($file_test_func))
      $this->test_func = $file_test_func;

    /* test to see if file is ok */
    $this->test_file();

    if ($parse) {
	    $this->parse_file();
    }
  } /* end __construct */

  function __destruct() {
    if ($this->parsed) {
      if ($this->mem) {
	      if (!$this->keep && isset($this->open_file)) {
		      unlink($this->filename);
	      }
      }
      else {
	flcose($this->fcont);
      }
    }
    unset($this->open_file);
    unset($this->keep);
    unset($this->fcont);
    unset($this->mem);
    unset($this->file_ok);
    unset($this->parsed);
    unset($this->test_func);
  } /* end __destruct */

  /* returns ok if:
   * 1) the file exists
   * 2) it's an uploaded file
   * 3) it has passed the supplied test-function
   */
  function file_ok() { return $this->file_ok; }

  private function parse_file() 
    {
      if (!$this->parsed && $this->file_ok) {
	      if ($this->file_ok() && isset($this->filename)) {
	  $fd = fopen($this->filename,'r');
	  $fsize=filesize($_FILES[$this->open_file]['tmp_name']);
	  if ($this->mem) {
	    $this->fcont = fread($fd, $fsize);
	    fclose($fd);
	  }
	  else {
	    $this->fcont = $fd;
	  }
	  $fuptr = $this->test_func;
	  /* truncate the pubkey_hash to the length defined in the config script */
	  $hash=pubkey_hash($this->fcont, true);
	  $auth_url = substr($hash,0,(int)Config::get_config('auth_length'));
	  $this->file_ok = $fuptr($this->fcont, $auth_url);
	}
	else {
	  $this->fcont = null;
	}
	$this->parsed = true;
      }
    } /* end parse_file() */

  function get_content() {
	  /* echo __FILE__.":".__LINE__. " " . $this->fcont . "<BR>\n"; */
	  if ($this->file_ok() && $this->parsed)
		  return $this->fcont;
  }

  /**
   * Write the content of the uploaded file to the path described by filepath.
   * Handy for instance for image uploads.
   *
   * @param $filepath The path to which the file's content is written.
   * @throws FileException If writing the file's content fails for some reason
   */
  public function write_content_to_file($filepath)
  {
      if ($this->file_ok()) {
	  $success = copy($_FILES[$this->open_file]['tmp_name'], $filepath);

	  if ($success === FALSE) {
	      throw new FileException("Could not write to location $filepath!");
	  }
      }
  }

  private function test_file()
    {
    /* check if $fname exists */
      if (isset($_FILES[$this->open_file]['name'])) {
	switch($_FILES[$this->open_file]['error']) {
	case UPLOAD_ERR_OK:
	  break;
	case UPLOAD_ERR_INI_SIZE:
	case UPLOAD_ERR_FORM_SIZE:
	  echo "Size of file exceeds maximum allowed filesize<BR>\n";
	  $this->file_ok = false;
	  return;
	case UPLOAD_ERR_PARTIAL:
	  echo "Upload did not finish properly, incomplete file. Try again<BR>\n";
	  $this->file_ok = false;
	  return;
	case UPLOAD_ERR_NO_FILE:
	  echo "No file given to upload-handler!<BR>\n";
	  $this->open_file = null;
	  $this->file_ok = false;
	  return;
	default:
	  echo "Unknown error condition!<BR>\n";
	  $this->file_ok = false;
	  return;
	}

	/* if nothing bad detected, assume it's OK (we still do the supplied test-function) */
	$this->file_ok = true;
      }
    } /* end test_file */

  /* trivial_test()
   *
   * This might deserve a place in somethingawful (or some such), but is
   * a function-holder to make the file-testing easier. If the user does not
   * supply a custom-test function, instead of testing to see if it is NULL, we
   * use this function. As we test for negative results, this test will never
   * affect the end-result and can be use safely (although it is rather useless
   * from a testing point of view).
   */
  private function trivial_test($content, $auth_url) {return $this->file_ok(); }

}
?>

	/**
	 * testError() see if any known error-conditions are set for the file
	 *
	 * @param void
	 * @return boolean true when <b>no</b> errors are detected.
	 */
	static function testError($fname)
	{
		if (isset($_FILES[$fname])) {
			switch($_FILES[$fname]['error']) {
			case UPLOAD_ERR_OK:
				return true;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				Framework::error_output("Size of file exceeds maximum allowed filesize.");
				return false;
			case UPLOAD_ERR_PARTIAL:
				Framework::error_output("Upload did not finish properly, incomplete file. Try again");
				return false;
			case UPLOAD_ERR_NO_FILE:
				Framework::error_output("No file given to upload-handler!");
				return false;
			default:
				Framework::error_output("Unknown error condition!");
				return false;
			}
			/* if nothing bad detected, assume it's OK (we still do the supplied test-function) */
			return true;
		}
	}

	/**
	 * getContent() return the content of the file
	 *
	 * Note: this function assumes assumes that testError has returned no
	 * errors. The only test is to see if the supplied name actually exists
	 * in the FILE-array
	 *
	 * @param  String the name of the upload-file name from the form.
	 * @return String|null the file read from /tmp
	 */
	static function getContent($fname)
	{
		if (isset($_FILES[$fname]['tmp_name'])) {
			$fsize	= filesize($_FILES[$fname]['tmp_name']);
			$fd	= fopen($_FILES[$fname]['tmp_name'],'r');
			$content= fread($fd, $fsize);
			fclose($fd);
			return $content;
		} else {
			return null;
		}
	}
