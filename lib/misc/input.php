<?php

class Input
{
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

		$input = htmlentities($input, ENT_QUOTES, 'UTF-8');
		/* remove paragraphs */
		$input = preg_replace('/(\r|\n)+/','', $input);

		if (ini_get("magic_quotes_gpc") === "1") {
			/* strip the slashes automatically inserted before doing more complete
			 * escaping */
			$input = stripslashes($input);
		}

		$output = mysql_real_escape_string($input);
		return $output;
	}

		/* Remove all url properties, since they can be abused for XSS attacks
		 * Attack vectors: javascript: links in IE (sic!)
		 * 				   -moz-binding: in Firefox
		 *
		 * more patterns: http://ha.ckers.org/xss.html
		 */
	static function sanitizeCSS($input)
	{
		$output = preg_replace('/(\n)?(.)*(url)\((.)*(;)?/','',$input);
		return $output;
	}
}
?>
