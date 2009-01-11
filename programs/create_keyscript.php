<?php
require_once('person.php');

class KeyScript {
	private $url;
	private $person;
	private $country;
	function __construct($pers) {
		if (isset($pers) && $pers->is_auth()) {
			$this->person = $pers;
			/* can use strstr for this, but in case common-name
			 * contains more than 1 . we use substring and search
			 * from the end (not head) */
			$this->country = strtoupper(substr($this->person->get_common_name(), 1+strrpos($this->person->get_common_name(), ".", -1)));
			$this->url = dirname($_SERVER['HTTP_REFERER']);

		}
	}

	public function create_script()
	{
		$script = file_get_contents(Config::get_config('programs_path'));

		/* set variables for the key and CSR/cert */
		$script = str_replace('country=""'	,'country="/C='	.$this->country . '"'			, $script);
		$script = str_replace('orgname=""'	,'orgname="/O='	.Config::get_config('cert_o') . '"'	, $script);
		$script = str_replace('orgunitname=""'	,'orgunit="/OU='.Config::get_config('cert_ou'). '"'	, $script);
		$script = str_replace('common=""'	,'common="/CN='	.$this->person->get_common_name() . '"'	, $script);
		$script = str_replace('key_length=""'	,"key_length="	.Config::get_config('key_length') . '"'	, $script);

		/* send location and upload related variables */
		$script = str_replace('server_loc=""'	,'server_loc="'.dirname($_SERVER['HTTP_REFERER']).'"'	, $script);
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
