<?php
/**
 * class Config
 *
 * Class for retrieving config-parameters from the config.
 * This makes usage more consistent, and it is easier to avoid bugs and
 * pitfals
 *
 * @author	Henrik Austad <henrik.austad@uninett.no>
 * @package	config
 */
require_once 'key_not_found.php';
require_once 'Config_Holder.php';
class Config
{
	private static $config;
	/**
	 * get_config()
	 *
	 * Simple wrapper-function that retrieves the given entry from the
	 * config-array.
	 * This is added as a step towards an invisible and protected config-array
	 *
	 * @param	String $entry_name the name of the config-switch to find
	 * @return	String the config-entry
	 * @access	public
	 * @throws	KeyNotFoundException
	 */
	static function get_config($entry_name)
	{
		if (!is_object(Config::$config)) {
			Config::$config = new Config_Holder();
		}
		return Config::$config->getConfigVal($entry_name);
	}
} /* end class Config  */

?>
