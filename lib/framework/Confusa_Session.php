<?php

  /**
   * CS : Confusa_Session
   *
   * This class is a container for the session-variables used within the portal.
   *
   * @author Henrik Austad <henrik@austad.us>
   */
class CS
{
	private static $cs_started = false;
	private static $name = "CS_Name";

	/**
	 * testSession() test and set the session to CS_Name
	 *
	 * This function is used to make sure that the session is indeed the
	 * CS_Name, the associated session.
	 *
	 * @param  : void
	 * @return : void
	 */
	private static function testSession()
	{
		if (session_name() != self::$name) {
			self::$cs_started = false;
		}

		if (!self::$cs_started) {
			self::$cs_started = true;
			session_name(self::$name);
			session_start();
		}
	}

	/**
	 * start() start the CS_Name session
	 *
	 * @param  : void
	 * @return : void
	 */
	public static function start()
	{
		self::testSession();
	}

	/**
	 * setSessionKey() take the value and store it in the session under $key
	 */
	public static function setSessionKey($key, $value)
	{
		self::testSession();
		$_SESSION[htmlspecialchars($key)] = htmlspecialchars($value);
	} /* end setSessionKey() */

	public static function getSessionKey($key)
	{
		self::testSession();
		if (isset($_SESSION) && array_key_exists(htmlspecialchars($key), $_SESSION)) {
			if (isset($_SESSION[htmlspecialchars($key)])) {
				return htmlspecialchars($_SESSION[htmlspecialchars($key)]);
			}
		}
		return null;
	} /* end getSessionKey() */

	/**
	 * dumpSession() dump the content of the session to stdout.
	 *
	 * This is only available when debug is enabled.
	 */
	public static function dumpSession()
	{
		if (Config::get_config('debug')) {
			self::testSession();
			echo "<pre>\n";
			echo "Session name. " . session_name() . "\n";
			print_r($_SESSION);
			echo "</pre>\n";
		}
	}
} /* end class CS */
?>
