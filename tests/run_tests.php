<?php

function print_status($name, $passed)
{
	$ESC_SEQ="\033[";
	$COL_GREEN=$ESC_SEQ."38;32;01m";
	$COL_RED=$ESC_SEQ."38;31;01m";
	$COL_RESET=$ESC_SEQ."00m";

	$nlen = floor((48 - strlen($name))/8);
	$tbs = "";
	for ($i=0;$i< $nlen;$i++)
		$tbs .= "\t";

	echo $COL_RESET.$name.":".$tbs."[ ";
	if ($passed) {
		echo $COL_GREEN . "  OK  ";
	} else {
		echo $COL_RED . "failed";
	}
	echo $COL_RESET." ]\n";
}

$dir = ".";
$files = scandir($dir);
$test_results = false;
echo "Running tests:\n";
foreach ($files as $file) {
	if (!is_file($file)) {
		continue;
	}
	/* must end with .php and not contain '#' or ~, and start with Test_ */
	$regex = '/^Test_.*\.php$/';
	if (preg_match($regex, $file)) {
		$objname =  substr($file, 0, -4);
		require_once $file;
		$t = new $objname;
		$res = $t->runTests();
		$test_results &= $res;
		print_status($t, $res);
	}
}
?>
