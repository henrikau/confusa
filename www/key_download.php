<?php
require_once 'confusa_include.php';	/* get path */
require_once 'config.php';

if (!Config::get_config('auth_bypass')) {
	include 'not_found.php';
	not_found($_SERVER['SCRIPT_NAME']);
	exit(0);
}
if (Config::get_config('maint')) {
	echo "1\n";
	echo "<h2>Under maintenance. Come back later.</h2>\n";
	exit(0);
}

require_once 'logger.php';
require_once 'csr_lib.php';
require_once 'person.php';
require_once 'confusa_gen.php';

/* only accept downloads from sources that specify *both* auth_var and
 * common_name (the client should know this anyway */
if (isset($_GET['inspect_csr']) && $_GET['common_name']) {
      $authvar        = htmlentities($_GET['inspect_csr']);
      $user           = base64_decode($_GET['common_name']);
      $person = new Person();
      $person->setEPPN($user);

      if(Config::get_config('standalone')) {
        $cm = new CertManager_Standalone($person);
      } else {
        $cm = new CertManager_Online($person);
      }

      try {
        $cert = $cm->get_cert($authvar);
        echo $cert;
      } catch (ConfusaGenException $e) {
        echo $e->getMessage() . "<br />\n";
      }
}
?>
