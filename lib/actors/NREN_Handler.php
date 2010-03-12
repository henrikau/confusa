<?php
  /**
   * NREN_Handler() class for finding and decorating an NREN from the provided
   * information.
   *
   * @author	Henrik Austad <henrik.austad@uninett.no>
   * @license	http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
   * @since	File available since post v0.6-rc0
   * @package resources
   */
require_once 'NREN.php';
require_once 'Input.php';
require_once 'MDB2Wrapper.php';

class NREN_Handler
{
	private $TYPE_DB_ID;
	private $TYPE_URL;
	private $TYPE_WAYF_URL;
	/**
	 * getNREN() find an NREN and return it based on provided key
	 *
	 * The key can be:
	 *	- the database-id of the NREN
	 *	- the wayf-url
	 *	- the idp_name
	 *
	 * typehints:
	 *	false	: ignore, do not use
	 *	0	: db-id
	 *	1	: URL
	 *	2	: WAYF-URL
	 *
	 * @param	mixed $key
	 * @param	int $type_hint hint to the type of the argument
	 * @return	NREN|null
	 * @access	public
	 */
	static function getNREN($key, $type_hint=false)
	{
		if ($type_hint) {
			switch($type_hint) {
			case self::$TYPE_DB_ID:
				return self::getByID(Input::sanitizeID($key));
			case self::$TYPE_URL:
				return self::getByURL(Input::sanitizeURL($key));
			case self::$TYPE_WAYF_URL:
				return self::getByWAYF(Input::sanitizeURL($key));
			default:
				/* didn't find a legal type_hint, ignore
				 * type_hint, fall out to standard. */
				break;

			}
		}
		$nren = self::getByID($key);
		if ($nren) {
			return $nren;
		}
		$nren = self::getByURL($key);
		if ($nren) {
			return $nren;
		}
		$nren = self::getByWAYF($key);
		if ($nren) {
			return $nren;
		}

		/* throw exception? */
		return false;
	} /* end getNREN() */

	private static function getByID($id)
	{
		if (!is_numeric($id)) {
			return false;
		}
		$query  = "SELECT idp_url from idp_map WHERE nren_id = ?";
		return self::getFromQuery($query, array('text'), array($id));
	}

	static function getByURL($nren_url)
	{
		$query  = "SELECT idp_url FROM idp_map idp LEFT JOIN nrens n ";
		$query .= "ON n.nren_id = idp.nren_id WHERE url = ?";
		return self::getFromQuery($query, array('text'), array($nren_url));
	} /* end getByURL */

	private function getByWAYF($wayf_url)
	{
		$query  = "SELECT idp_url FROM idp_map idp LEFT JOIN nrens n ";
		$query .= "ON n.nren_id = idp.nren_id WHERE wayf_url = ?";
		return self::getFromQuery($query, array('text'), array($wayf_url));
	}

	private function getFromQuery($query, $params, $data)
	{
		try {
			$res = MDB2Wrapper::execute($query, $params, $data);
			if (count($res) == 1) {
				if (array_key_exists('idp_url', $res[0])) {
					return new NREN($res[0]['idp_url']);
				}
			}
		} catch (ConfusaGenException $cge) {
			Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ .
			                  " error with db-connect. " . $cge->getMessage());
		}
		return false;
	}
}
?>