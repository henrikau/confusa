<?php
include('key_not_found.php');
class Config {
     private static $config;
     /* get_config()
      *
      * Simple wrapper-function that retrieves the given entry from the
      * config-array.
      * This is added as a step towards an invisible and protected config-array
      */
     static function get_config($entry_name)
          {
               if (!is_object(Config::$config)) {
                    Config::$config = new Config_Holder();
               }
               try {
                    return Config::$config->get_config_val($entry_name);
               }
               catch (KeyNotFoundException $knfe) {
                    echo "Key not found! -> " . $knfe->getMessage() . "<br>\n";
                    /* throw $knfe; */
               }
               catch (Exception $e) {
                    echo "unhandled exception!<br>\n" . $e->getMessage() . "<br>\n";
                    exit(1);
               }
               return Null;
          }
} /* end class Config  */

/* Config_Holder
 *
 * Holder class for config. Do not use this class directly, it is a way of
 * implementing the singleton-concept of PHP (it is, AFAIK, impossible to create
 * a static get-function withing a class to guarantee only one instance of a
 * given class.
 * So, we create a 'default' config class, and let that create only one instance
 * of Config_Holder.
 */
final class Config_Holder {
     function __construct()
          {
               global $confusa_config;
               require_once(dirname(dirname(__FILE__)) . '/config/confusa_config.php');
               if (!isset($confusa_config)){
                    echo "Cannot load config-object when confusa_config.php does not exist!<br>\n";
                    exit(1);
               }
               $this->config = $confusa_config;
          }
     function get_config_val($entry)
          {
               if (!array_key_exists($entry, $this->config))
                    throw new KeyNotFoundException("<FONT CLASS=\"exception\">Did not find " . $entry . " in config</FONT>");
               return $this->config[$entry];
          }
     
}
?>
