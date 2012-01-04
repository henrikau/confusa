<?php
require_once '../www/confusa_include.php';
  /**
   * Test - simple Test-infrastrucutre
   *
   * To avoid as many stupid mistakes, this is a testframework for Confusa. It
   * does not aim to catch all errors, but if it can be tested for, it should be
   * added here to reduce the errors as much as possible.
   *
   *
   * This is the 'master' class, giving a few basic functions to run.
   */
abstract class Test
{
	private $file_folder = "files/";
	protected $name;
	protected $valid;
	function __construct($name)
	{
		$this->name = $name;
		$this->valid = true;
		if (!is_dir($this->file_folder)) {
			if (is_writable('.')) {
				$this->printMsg("Making directory");
				mkdir($this->file_folder);
			}
		}
	}

	function __toString()
	{
		return (string)$this->name;
	}

	/**
	 * getFile() read a file fom datastorage.
	 *
	 * Often when we do tests, we compare computed results to stored data
	 * which we know to be correct.
	 *
	 * The convention is to store test-specific data is in
	 * tests/files/<name>/, and this function makes it easy to retrieve this.
	 *
	 * @param	String name of the file to read
	 * @return	String|null the content of the file or null if not found
	 * @access	protected
	 */
	protected function getFile($filename)
	{
		$path = 'files/'.$this->name."/".$filename;
		if (!is_file($path)) {
			$this->printMsg("Cannot open $path, test will fail.");
			$this->valid = false;
			return null;
		}
		try {
			$file = file_get_contents($path, false, null, 0, filesize($path));
			if (!$file) {
				$this->printMsg("File $path not found!");
				$this->valid = false;
				return null;
			}
		} catch (IOException $ioe) {
			$this->valid = false;
			return null;
		}
		return $file;
	}

	protected function printMsg($msg)
	{
		$file = fopen("test.log", "a+");
		if (!$file) {
			echo "Could not open file for writing!\n";
			return;
		}
		fwrite($file,"[" . $this->name . "] ".date('Y-m-d H:i:s') ." $msg\n");
		if (!fclose($file))
			echo "Error closing logfile!";
	}
	/**
	 * runTests() run all implementationspecific tests
	 *
	 * This is the actual test, all callees must implement the relevant
	 * tests here.
	 */
	abstract function runTests();
}


?>
