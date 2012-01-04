<?php
require_once 'Test.php';
require_once 'MDB2Wrapper.php';
require_once 'CSR.php';
class Test_CSR extends Test
{
	function __construct()
	{
		parent::__construct("CSR");
	}
	public function runTests()
	{
		return $this->getFromDB();
	}

	public function getFromDB()
	{
		try {
			$allcsrs = MDB2Wrapper::execute('SELECT common_name, auth_key FROM csr_cache',
							null,
							null);
		} catch (Exception $e) {
			$this->printMsg($e->getMessage());
			return false;
		}
		if (count($allcsrs) < 1) {
			$this->printMsg("No CSRs in the database to test, test cannot continue.");
			return false;
		}
		foreach($allcsrs as $key => $csrItem) {
			$csr = CSR::getFromDB($csrItem['common_name'], $csrItem['auth_key']);
			$this->printMsg($csrItem['auth_key']);
			$this->printMsg($csr->getSubject());
			$this->printMsg($csr->getAuthToken());
			echo "\n";
		}
		$list = CSR::listPersonCSRs("Jane Doe");
		print_r($list);
		return true;
	}
}
?>
