<?php
require_once 'person.php';
/* Class KeyScript
 * Class for modifying the create_cert script based on config-parameters and attributes from SimpleSAMLphp
 *
 * Author: Henrik Austad <henrik.austad@uninett.no>
 */
class KeyScript {
	private $person;
	function __construct($pers) {
		if (isset($pers) && $pers->is_auth()) {
			$this->person = $pers;
		}
	}

	function preg_replace_callback($matches)
	{
		echo $matched[0];
		return $matches[1].$matches[2].get_config($matches[1]).$matches[3];
	}

	public function create_script()
	{

		$script = file_get_contents(Config::get_config('programs_path'));

 		/* set variables for the key and CSR/cert */
		$script = str_replace('common=""'      ,'common="' .$this->person->get_valid_cn() . '"'	, $script);
		$script = str_replace('full_dn=""'	,'full_dn="'.$this->person->get_complete_dn() . '"'	, $script);
		$script = str_replace('key_length='	,"key_length="	.Config::get_config('key_length')	, $script);

		/* send location and upload related variables */
		$address = "http";
		if ($_SERVER['SERVER_PORT'] == "443") {
			$address .= "s";
		}
		$address .= "://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);

		$script = str_replace('server_loc=""'	,'server_loc="'. $address .'"'	, $script);
                $script = str_replace('down_page=""'	,'down_page="'.Config::get_config('download').'"'	, $script);
                $script = str_replace('up_page=""'	,'up_page="'.Config::get_config('upload').'"'		, $script);
		$script = str_replace('approve_page=""'	, 'approve_page="'.Config::get_config('approve').'"'	, $script);
		$script = str_replace('csr_var=""'	,'csr_var="'.Config::get_config('csr_var').'"'		, $script);
		$script = str_replace('auth_var=""'	,'auth_var="'.Config::get_config('auth_var').'"'	, $script);
		$script = str_replace('auth_length=""'	,'auth_length="'.Config::get_config('auth_length').'"'	, $script);
		$script = str_replace('ca_cert_name="'	, 'ca_cert_name="'.Config::get_config('ca_cert_name')	, $script);
		$script = str_replace('ca_cert_path="'	, 'ca_cert_path="'.Config::get_config('ca_cert_path')	, $script);

		/* how to download with wget */
		$wget_options="--html-extension ";
		if (!Config::get_config('script_check_ssl')) {
			$wget_options.="--no-check-certificate ";
		}
		$script = str_replace('wget_options=""','wget_options="'.$wget_options.'"', $script);

		/* set error_addr for where the script should send potential
		 * error messages */
		$script = str_replace('error_addr="', 'error_addr="'.Config::get_config('error_addr'), $script);

		return $script;
	}
  }
?>
