<html>
<head><title>Test-suite for Confusa</title></head>
<body>
<h1>Test suite for Confusa</h1>
<?php
$start="test_";
$end=".php";
$f = scandir(dirname(__FILE__));

foreach ($f as $key => $file) {
	if ((strncmp(substr($file, 0, strlen($start)), $start, strlen($start)) == 0) &&
		(strncmp(substr($file, -strlen($end)), $end, strlen($end)) == 0)) {
		/* fixme handle https-connections */
		$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $file;
		echo "<iframe src=\"$url\" seamless=\"seamless\" width=\"1000\"></iframe><br />\n";
	}
}

?>

</body>
</html>
