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
			$this->url = Config::get_config('server_url');

		}
	}

	public function create_script()
	{
		/* read skeleton-script from file */
             $script = file_get_contents(Config::get_config('programs_path'));
		/* set fiedls for subject in CSR */
		$script = str_replace('="/C=','="/C='.$this->country, $script);
		$script = str_replace('="/CN=','="/CN='.$this->person->get_common_name(), $script);
		$script = str_replace('="/emailAddress=','="/emailAddress='.$this->person->get_email(), $script);
		$script = str_replace("key_length=","key_length=".Config::get_config('key_length'), $script);

		/* send location and upload related variables */
		$script = str_replace('server_loc=""','server_loc="'.Config::get_config('server_url').'"', $script);
                $script = str_replace('down_page=""' , 'down_page="'.Config::get_config('download').'"', $script);
                $script = str_replace('up_page=""' , 'up_page="'.Config::get_config('upload').'"', $script);


		$script = str_replace('csr_var=""','csr_var="'.Config::get_config('csr_var').'"', $script);
		$script = str_replace('auth_var=""','auth_var="'.Config::get_config('auth_var').'"', $script);
		$script = str_replace('auth_length=""','auth_length="'.Config::get_config('auth_length').'"', $script);

		/* how to download: */
		$wget_options="-O /dev/null ";
		if (!Config::get_config('script_check_ssl')) {
			$wget_options.=" --no-check-certificate";
		}
		$script = str_replace('wget_options=""','wget_options="'.$wget_options.'"', $script);

		/* set error_addr */
		$script = str_replace('error_addr="', 'error_addr="'.Config::get_config('error_addr'), $script);

		/* echo "<pre>".$script."</pre>\n"; */
		return $script;
	}
  }
?>
