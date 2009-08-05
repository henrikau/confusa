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
}
?>
