<?php
/**
 * class Config_Holder
 *
 * Holder class for config. Do not use this class directly, it is a way of
 * implementing the singleton-concept of PHP (it is, AFAIK, impossible to create
 * a static get-function withing a class to guarantee only one instance of a
 * given class.
 *
 * So, we create a 'default' config class, and let that create only one instance
 * of Config_Holder.
 *
 * @author	Henrik Austad <henrik@austad.us>
 * @package	config
 */
final class Config_Holder {
	function __construct()
	{
		global $confusa_config;
		require_once 'confusa_config.php';
		if (!isset($confusa_config)){
                    echo "Cannot load config-object when confusa_config.php does not exist!<br>\n";
                    exit(1);
		}
		$this->config = $confusa_config;
	}

	/**
	 * getConfigVal() return the key from config
	 *
	 * @param	String $entry the value to find
	 * @return	String the value
	 * @throws	KeyNotFoundException
	 * @access	public
	 */
	public function getConfigVal($entry)
	{
		if (!array_key_exists($entry, $this->config))
			throw new KeyNotFoundException("Did not find " . $entry . " in config!");
		return $this->config[$entry];
	}
} /* end class Config_Holder */
?>
