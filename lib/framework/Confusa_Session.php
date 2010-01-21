<?php
require_once "confusa_constants.php";
  /**
   * CS : Confusa_Session
   *
   * This class is a container for the session-variables used within the portal.
   *
   * @author Henrik Austad <henrik@austad.us>
   */
class CS
{

	/**
	 * testSession() test and set the session to the correct name.
	 *
	 * This function is used to make sure that the session is set. For
	 * compatibility with SimpleSAMLphp and to avoid strange session-errors,
	 * we have set the default name to PHPSESSID in lib/confusa_constants.php
	 *
	 * @param  : void
	 * @return : void
	 */
	private static function testSession()
	{
		if (session_name() != ConfusaConstants::$SESSION_NAME) {
			session_name(ConfusaConstants::$SESSION_NAME);
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
