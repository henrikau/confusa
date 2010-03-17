<?php
  /**
   * class CA_Handler - get the correct CA manager for the running portal.
   *
   * As the portal can use different CA-managers, we use this class to find the
   * 'correct' handler.
   *
   * @author Henrik Austad <henrik@austad.us>
   * @package ca
   */
require_once 'Config.php';

class CA_Handler
{
	private static $cam;

	/**
	 * getCA() return the CA-manager based on the portal configuration.
	 */
	static function getCA()
	{
		if(!isset(CA_Handler::$cam)) {
			switch((int)Config::get_config('ca_mode')) {

			case CA_STANDALONE:
				require_once 'CA_Manager_Standalone.php';
				CA_Handler::$cam = new CA_Manager_Standalone();
			case CA_COMODO:
				require_once 'CA_Manager_Comodo.php';
				CA_Handler::$cam = new CA_Manager_Comodo();
			default:
				Logger::log_event(LOG_ALERT, "Tried to instantiate CA " .
						  Config::get_config('ca_mode') .
						  " but this is an unknow mode.");
				return false;
			}
		}
		return CA_Handler::$cam;
	} /* end getCA() */
} /* end class CA_Handler */

?>