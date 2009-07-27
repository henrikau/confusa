<?php
class Output
{
	/**
	 * create_link - create a full <A HREF.. link
	 *
	 * @url		: the url we are linking to
	 * @url_name	: the name, the 'name' attribute in the A-tag
	 * @name	: the name that is shown in the webpage for the user.
	 */
	static function create_link($url, $url_name=null, $name=null)
	{
		if (!isset($url)){
			echo "url not set!";
			return null;
		}
		$loc_url	= $url;
		$loc_url_name	= $url_name;

		if (!isset($url_name)) {
			$loc_url_name = basename($url);
			if (strpos($loc_url_name, ".") !== FALSE) {
				$loc_url_name = substr($loc_url_name, 0, strpos($loc_url_name, "."));
			}
		}

		$loc_name	= (isset($name) ? $name : $loc_url_name);

		return "<A HREF=\"$loc_url\" name=\"$loc_name\">$loc_url_name</A>";
	} /* end create_link */

	/**
	 * create_select_box - create a form select-box
	 *
	 * @active	: The active option (pre-selected)
	 * @choices	: The array of options. Empty choices ("") will be discarded
	 *		  without warning.
	 * @sel_name	: The name of the form-variable from the select section
	 */
	static function create_select_box($active, $choices, $sel_name)
	{
		$res = "<select name=\"$sel_name\">\n";
		foreach($choices as $element) {
			if ($element !== "") {
				$res .= "<option value=\"$element\" ";
				$res .=  ($element == $active ?  " selected=\"selected\"":"");
				$res .=  " >" . $element . "</option>\n";
			}
		}

		$res .= "</select>\n";
		return $res;
	} /* end create_select_box */

} /* end class Output */

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
			$help = " [ " . show_window("?", "messages/diff_ip.php") . " ] ";
		}
	}
	return "$pre$ip$post$help";

}

function show_window($url_name, $target)
{
	$help  = " <A HREF=\"\"";
	$help .= "onClick =\"window.open('" . $target . "', '', 'width=500,height=400');\"";
	$help .= ">";
	$help .=  $url_name . "</A> \n";
	return $help;
}


?>