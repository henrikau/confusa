<?php

require_once 'confusa_constants.php';

mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

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

	/**
	 * sanitize a subscriber org-name (the /O= name in the subject DN).
	 * This function does not perform any validation whatsoever, it just removes
	 * characters that are not meant to be in subject-DN org-name.
	 * @param $input string an input which is supposed to be a subscriber
	 *               org-name
	 * @return string the sanitized input string
	 */
	static function sanitizeOrgName($input)
	{
		$output = preg_replace('/[^a-z0-9@_,\.\s]/i', '', $input);
		return $output;
	}

	/**
	 * sanitize an anti-CSRF-token. Only characters, numbers and the
	 * delimiting ':' is allowed, all other values are discarded.
	 *
	 * @param	String	$token	the anti-CSRF token
	 * @return	String		the sanitized token
	 * @access	public
	 */
	static function sanitizeAntiCSRFToken($token)
	{
		return preg_replace('/[^0-9]+[:][^a-f0-9]/i', '', $token);
	}

	/**
	 * Sanitize Confusa's internal representation of a subscriber-name. This
	 * equals the value sent in the attribute identifying the subscriber
	 * (eduPersonOrgDN, schacHomeOrganization).
	 * No validation, just removal of "bad" characters
	 * @param $input string The unsanitized subscriber-db-name
	 * @return string The sanitized subscriber-IdP-name
	 */
	static function sanitizeIdPName($input)
	{
		$output = preg_replace('/[^a-z0-9_=,:\/\s\.\-@]/i','', $input);
		return $output;
	}

	/**
	 * Sanitize a numeric ID (like the primary key in a DB)
	 * @param $input string ID input
	 * @return integer the input with all non-numeric components removed
	 */
	static function sanitizeID($input)
	{
		$output = preg_replace('/[^0-9]/', '', $input);
		return $output;
	}

	/**
	 * Sanitize an org-state. Return the input string, if it is found in the
	 * org-states in Confusa's constants or return an empty string otherwise.
	 * @param $input string The org-state
	 * @return string The input, if in ConfusaConstants::$ORG_STATES or the
	 *                empty string otherwise
	 */
	static function sanitizeOrgState($input)
	{
		if (array_search($input, ConfusaConstants::$ORG_STATES) !== false) {
			return $input;
		} else {
			return '';
		}
	}

	/**
	 * Sanitize an e-mail address. No validation, just dropping unwanted
	 * characters.
	 * @param $input string the unsanitized e-mail address
	 * @return string the sanitized e-mail address
	 */
	static function sanitizeEmail($input)
	{
		$output = preg_replace('/[^a-z0-9._%\+\-@\.]/i', '', $input);
		return $output;
	}

	/**
	 * Sanitize a phone number. A phone number may contain numbers and the +
	 * symbol.
	 * Drop all other characters.
	 * @param $input string unsanitized phone number
	 * @return string the sanitized phone number
	 */
	static function sanitizePhone($input)
	{
		$output = preg_replace('/[^\+0-9]/', '', $input);
		return $output;
	}
	/**
	 * sanitizePersonName()
	 *
	 * Sanitize the name of a person. Allow UTF-8 characters, spaces, '.'
	 * and hyphens ('-') symbols for initials.  Drop all other
	 * characters.
	 *
	 * Due to the UTF-8 regex, this function is slower than normal
	 * sanitation (measured factor 3 to around 8, although on a
	 * few-10-microseconds scale).
	 *
	 * @param	string $input the unsanitized name
	 * @return	string $output the sanitized name
	 * @access	private
	 * @static
	 */
	static function sanitizePersonName($input)
	{
		$output = preg_replace('|--|i', '-', $input);
		$output = mb_ereg_replace('[^[:alpha:]\-\s\.]', '', $output, 'ip');
		return $output;
	}

	/**
	 * Allow the union of the charsets in sanitizePersonName and sanitizeEPPN.
	 * NB: Uses UTF-8 compatible mb_ereg_replace, which is slower than normal
	 * regex-replacing. Don't use excessively!
	 * @param $input string unsanitized common name
	 * @return string sanitized common name
	 */
	static function sanitizeCommonName($input)
	{
		$output = mb_ereg_replace('[^[:alpha:]\s\.0-9@_\-\+=/,]', '', $input, 'ip');
		return $output;
	}

	/**
	 * Sanitize an URL. Include most characters needed for protocol-, host-,
	 * domain- and query-part. Drop the rest. No punycode URLs.
	 *
	 * This will also strip all excessive // in the URL except after the
	 * https://-bit.
	 *
	 * @param $input the unsanitized URL
	 * @return string the sanitized URL
	 */
	static function sanitizeURL($input)
	{
		$output = preg_replace('|[^\:/\.a-z0-9\-\?&%\=~_]|i', '', $input);
		$output = preg_replace('|\://[/]*|i', '://', $output);
		/* strip excessive //'s*/
		$pos = strpos($output, "://");
		if ($pos > 0) {
			$pos += strlen("://");
			$output = substr($output, 0, $pos) .
				preg_replace("/[\/]+/","/", substr($output, $pos));
		}
		return $output;
	}

	/**
	 * Sanitize an eduPersonPrincipalName. No validation, just dropping of
	 * undesired characters.
	 * @param $input string the unsanitized ePPN
	 * @return string the santitized ePPN
	 */
	static function sanitizeEPPN($input)
	{
		$output = preg_replace('/[^a-z0-9@_\.\-\+]/i', '', $input);
		return $output;
	}

	/**
	 * Sanitize the entitlement attribute. Typical characters will include
	 * alphanumerics and a colon ':'. Allow also '_' and '-', drop the rest.
	 * @param $input string An unsanitized entitlement attribute.
	 * @return string The sanitized entitlement-string.
	 */
	static function sanitizeEntitlement($input)
	{
		$output = preg_replace('/[^a-z0-9_\-]/i', '', $input);
		return $output;
	}

	/**
	 * Sanitize a NREN-name-string. Alphanumerics, '.', '-' and '_'. The site
	 * admin can pick the NREN-name him/herself, so we can be stricter in this
	 * validation.
	 * @param $input string the unsanitized NREN-name
	 * @return string the sanitized NREN-name
	 */
	static function sanitizeNRENName($input)
	{
		$output = preg_replace('/[^a-z0-9_\-\.]/i','', $input);
		return $output;
	}

	/**
	 * Sanitize the main cert-identifier (auth-key/order-number). The
	 * order-number is numeric and the auth_key is a pubkey-hash which is a
	 * hexadecimal sequence so 0-9 and a-f are allowed, the rest is dropped.
	 * @param $input string The unsanitized string
	 * @return the sanitized string
	 */
	static function sanitizeCertKey($input)
	{
		$output = preg_replace('/[^0-9a-f]/i', '', $input);
		return $output;
	}

	/**
	 * Sanitize a language code. Can be of the form 'no', 'sv' or 'de', but also
	 * for instance 'en-GB', 'de-AT' aso. should be legal.
	 *
	 * @param $input string unsanitized language code
	 * @return sanitized language code
	 */
	static function sanitizeLangCode($input)
	{
		$output = preg_replace('/a-z\-/i', '', $input);
		return $output;
	}

	/**
	 * Sanitize base64, somewhat geared towards CSRs. They follow a quoted-
	 * printable-encoding, with characters a-z, 0-9, '+', '/' and '=' as the
	 * final delimiter. -- and whitespace is used in the header and footer.
	 * @param $input string the unsanitized base64-string
	 * @return string the sanitized base64-string
	 */
	static function sanitizeBase64($input)
	{
		$output = preg_replace('/a-z0-9\+\/\-\s=/i', '', $input);
		return $output;
	}

	/**
	 * Sanitize a string which should contain only numeric (0-9) content.
	 * Everything else will be stripped.
	 *
	 * @param $input string the unsanitized numeric-string
	 * @return string the sanitized numeric string
	 */
	static function sanitizeNumeric($input)
	{
		$output = preg_replace('/[^0-9]/', '', $input);
		return $output;
	}

	/**
	 * Sanitize a string which should contain only alphabetical characters
	 * [a-zA-Z]. Everything else will be stripped.
	 *
	 * @param $input string the unsanitized alphabetical-string
	 * @return string the sanitized alphabetical string
	 */
	static function sanitizeAlpha($input)
	{
		$output = preg_replace('/[^a-z]/i', '', $input);
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

		$input = stripslashes($input);

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
