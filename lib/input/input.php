<?php

class Input
{
	private static $bootstrapped = false;
	/**
	 * sanitize() - clean input for known injection vulnerabilities
	 *
	 * Remove anything that could be dangerous from user input.
	 * Our organization names should contain only [a-z][0-9], like the nren
	 * names, like the states. So all inputs can be limited to [a-z][0-9]
	 *
	 * TODO: This function spphould be accessible for all forms taking data
	 * TODO: Make sure it accepts all legal characters in the \DN
	 */
	static function sanitize($input)
	{
		if (!isset($input) || $input === "")
			return null;

		if (is_array($input)) {
			foreach($input as $var=>$val) {
				$output[$var] = Input::sanitize($val);
			}
		}

		$output = preg_replace('/[^a-z0-9_.@ ]+/i','',$input);
		return $output;
	}

	/*
	 * For text e.g. defined by the NREN admin to view on the help/about page
	 * we can not make too many assumptions about how the input will look like
	 * It must be possible to represent both special characters like åäö and
	 * characters like ' in the floating text.
	 *
	 * That's why we just try to get rid of SQL-injection-ish characters as well
	 * as HTML characters and leave the rest of the text untouched.
	 *
	 * @param $input the text which we want to sanitize
	 */
	static function sanitizeText($input)
	{
		if (!isset($input) || empty($input)) {
			return null;
		}

		if (is_array($input)) {
			foreach($input as $var=>$val) {
				$output[$var] = Input::sanitizeText($val);
			}
		}

		if (ini_get("magic_quotes_gpc") === "1") {
			/* strip the slashes automatically inserted before doing more complete
			 * escaping */
			$input = stripslashes($input);
		}

		/* in text is feasible to want newlines, to format the appearance of the
		 * text. Since it is undesired to directly insert newlines into the DB
		 * convert them to <br /> tags. Direct HTML insertion has been dealt
		 * with using htmlentities*/
		$input = strtr(strip_tags($input), array("\n" => '<br />', "\r\n" =>'<br />'));

		/* The following is a *HACK*
		 * However, since we want to use the mysql_real_escape_string,
		 * we have to make sure that the database has been
		 * contacted. *sigh*
		 *
		 * Note that this *may* throw an exception from the database.
		 */
		if (!Input::$bootstrapped) {
			MDB2Wrapper::execute("SELECT current_timestamp()", null, null);
			Input::$bootstrapped = true;
		}
		/* Escape the string */
		$output = mysql_real_escape_string($input);
		return $output;
	}

		/* Remove all url properties, since they can be abused for XSS attacks
		 * Attack vectors: javascript: links in IE (sic!)
		 *				   -moz-binding: in Firefox
		 *
		 * Also, remove all statements starting with (, since IE has a dynamic
		 * property called expression() which will execute arbitrary
		 * JavaScript in the stylesheet. Normal CSS properties don't need
		 * brackets.
		 *
		 * more patterns: http://ha.ckers.org/xss.html
		 */
	static function sanitizeCSS($input)
	{
		$output = preg_replace('/(\n)?(.)*(url)(.)*(;)?/','',$input);
		/* execute this after the URL removal, since it will break the CSS.
		 * this is for the leftover hardcore cases such as expression(...) */
		$output = preg_replace('/(.)*(\()+(.)*/', '', $output);
		/* remove all occurences of @ as the @import directive makes it possible
		 * to execute remote code
		 */
		$output = preg_replace('/(.)*(@)+(.)*/', '', $output);
		return $output;
	}

	/**
	 * Convert a break <br /> back to a newline.
	 * <br /> is a relatively safe way to store linebreaks in the DB. That's why
	 * we use it as a storage format for linebreaks. In certain cases, however,
	 * we need back the old newlines, for instance for a textarea, as input
	 * to the Textile transcoder or before passing data through htmlentities.
	 */
	static function br2nl($input)
	{
		$output = strtr($input, array("<br />" => "\n"));
		return $output;
	}
}
?>
