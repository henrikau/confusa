<?php
session_start();
echo "<pre>\n";
echo "GET:\n";
print_r($_GET);
echo "\nPOST:\n";
print_r($_POST);
echo "</pre>\n";

phpinfo();
?>