<?php
  /* emulate MDB2Wrapper, tailor query for the parts we need.
   *
   * Note, this is *not* a drop-in replacement for db-connector, this is a way
   * to _specifically_ tailor the query-results to trigger different
   * corner-cases in the system.
   *
   * It is not exactly easy  to use ;)
   */
class MDB2Wrapper
{
	private static $mode = -1;

	const NRENAccountError = 0;
	const NRENAccountOK = 1;
	public static function execute($query, $types, $data, $update = false)
	{
		switch (self::$mode) {
		case MDB2Wrapper::NRENAccountError:
			return self::NRENAccountMultipleResponse();
		case MDB2Wrapper::NRENAccountOK:
			return self::NRENAccountOK();
		}
		return;
	}

	/* drop writes */
	public static function update($query, $types, $data)
	{
		return;
	}

	public static function setMode($mode)
	{
		self::$mode = $mode;
	}
	private static function NRENAccountMultipleResponse()
	{
		$res = array();
		$res[0] = array('account_map_id' => 1,
						'login_name' => "Test",
						'password' => "foobar",
						'ivector' => "deadbeef",
						'ap_name' => "Confusa");
		$res[1] = array('account_map_id' => 2,
						'login_name' => "Test",
						'password' => "foobar",
						'ivector' => "deadbeef",
						'ap_name' => "Confusa");

		return $res;
	}
	private static function NRENAccountOK()
	{
		return "";
	}	
}
?>