<?php
  /**
   * class PW
   *
   * Small class for creating and scrambling passwords
   *
   * @author Henrik Austad <henrik.austad@uninett.no>
   */
class PW
{
	/** create() create a random string of text
	 *
	 * Creates a random password using the base (variable $base) specified below.
	 * Numbers 0 and 1 have been removed because they can be mistaken for 'O' and 'l'
	 *
	 * For change of characters allowed in password, edit $base
	 *
	 * @param  int $length the length of the password
	 * @return String $gen_pw the generated password
	 */
	static function create($length=8) {
		$base ="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ23456789";
		$base_length = strlen($base);
		$gen_pw = "";
		$counter = 0;
		// loop for $length rounds and append a character to the result
		// also, it's no longer necessary to seed rand() with srand().
		while ($counter < $length) {
			$index = rand(0, $base_length);
			$gen_pw = $gen_pw . substr($base, $index, 1);
			$counter++;
		}
		return $gen_pw;
	} /* end create() */
} /* end classPW */
?>
