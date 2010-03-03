<?php
class Output
{
	/**
	 * Return an ASCII string for the given UTF-8 input string
	 * First map known UTF-8 chars to their ASCII counterparts, then hard-remove
	 * the rest.
	 *
	 * @param $inputStr The UTF-8 input string
	 */
	static function mapUTF8ToASCII($inputStr)
	{
		 /* map known UTF8-characters to ASCII characters */
		$map = ConfusaConstants::$UTF8_ASCII_MAP;
		$asciiString = str_replace(array_keys($map), array_values($map), $inputStr);
		/* remove the rest of the ACSII characters the "hard way" */
		$asciiString = preg_replace("/[^a-z0-9_.@ \d]/i", "", $asciiString);
		return $asciiString;

	}
	/**
	 * formatIP() take an IP, match it to the client's and return a formatted string
	 *
	 * This is useful when listing an IP and you want to issue a warning if the IP
	 * has changed. The function can also show a help-text (a box will pop up)
	 * explaining what the problem is.
	 *
	 * @param $ip String :		the IP-address to format.
	 * @param $show_help Boolean :	whether or not to display a help-box at the
	 *				user's request
	 *
	 * @return $ipmsg String :	The formatted IP-address.
	 */
	static function formatIP($ip, $show_help=false)
	{
		$ipmsg = $ip;
		if ($_SERVER['REMOTE_ADDR'] != $ip){
			$ipmsg = "<span style=\"color: red\"><i>$ip</i></span>";
			if ($show_help) {
				$help  = "<a href=\"\"";
				$help .= "onclick =\"window.open('messages/diff_ip.php', '', 'width=500,height=400');\"";
				$help .= ">";
				$help .=  "<img src=\"graphics/flag_red.png\" class=\"url\" title=\"IP addresses differ!\"> $ipmsg</a>";
			}
		}
		return $ipmsg;
	}

	/**
	 * getUserAgent - return the browser of the user
	 * certificate deployment scripts and similar javascript functionality
	 * needs browser-specific treatment
	 *
	 * @return The name of the user-agent of the user
	 */
	static function getUserAgent()
	{
		$userAgent=$_SERVER['HTTP_USER_AGENT'];

		/* Chrome sends both "AppleWebKit" and "like Gecko", but does not support
		 * keygen
		 */
		if (stripos($userAgent, "chrome") !== FALSE) {
			return "other";
		}

		if (stripos($userAgent, "msie") !== FALSE) {
			if (stripos($userAgent, "windows NT 5.") !== FALSE) {
				return "msie_pre_vista";
			} else {
				return "msie_post_vista";
			}
		} else if (stripos($userAgent, "applewebkit") !== FALSE ||
			   stripos($userAgent, "opera") !== FALSE ||
			   stripos($userAgent, "gecko") !== FALSE) {
			return "keygen";
		} else {
			return "other";
		}
	} /* end getUserAgent() */

} /* end class Output */

?>
