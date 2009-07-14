<?php
function error_output($msg)
{
	echo "<FONT COLOR=\"RED\"><B>\n";
	echo $msg . "<BR>\n";
	echo "</B></FONT>\n";
}

function decho($msg)
{
	if (Config::get_config('debug')) {
		echo $msg . "<BR>\n";
	}
}
function db_array_debug($array, $msg=null)
{
	if (Config::get_config('debug') && count($array) > 1) {
		if (isset($msg))
			echo $msg . "<BR>\n";
		echo "<PRE>\n";
		print_r($array);
		echo "</PRE>\n";
	}
}

function format_ip($ip, $show_help=false)
{
	$pre = "";
	$post = "";
	$help = "";
	if ($_SERVER['REMOTE_ADDR'] != $ip){
		$pre =  "<FONT COLOR=\"RED\"><B><I>";
		$post = "</I></B></FONT>";
		if ($show_help) {
			$msg  = "The address of the machine uploading a CSR is recorded. ";
			$msg .= "If, for any reason, the address of your current machine differs from that, a notification ";
			$msg .= "is displayed. This may, or may not, mean anything, but you should be aware of this. ";
			$msg .= "Your current IP is " . $ip;
			$help = " [ " . show_window("?", $msg) . " ] ";
		}
	}
	return "$pre$ip$post$help";

}

function show_window($url_name, $message)
{
	$help  = " <A HREF=\"\"";
	$help .= "onClick =\"window.open('" . $message . "', '', 'width=400,height=300');\"";
	$help .= ">";
	$help .=  $url_name . "</A> \n";
	return $help;
}
?>