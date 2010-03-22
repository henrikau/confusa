<?php
  /**
   * NREN_Handler() class for finding and decorating an NREN from the provided
   * information.
   *
   * @author	Henrik Austad <henrik.austad@uninett.no>
   * @license	http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
   * @since	File available since pre v0.6-rc0
   * @package resources
   */
require_once 'NREN.php';
require_once 'Input.php';
require_once 'MDB2Wrapper.php';

class NREN_Handler
{

	/**
	 * getNREN() find an NREN and return it based on provided key
	 *
	 * This is a 'guess all' approach. If you know the type of key, consider
	 * calling the matching routine directly.
	 *
	 * The key can be:
	 *	- the database-id of the NREN
	 *	- the wayf-url
	 *	- the idp_name
	 *
	 * @param	mixed $key
	 * @return	NREN|null
	 * @access	public
	 */
	static function getNREN($key)
	{
		/* try URL first, this is via the idp_map, the most common case  */
		$nren = self::getByIdPURL(Input::sanitizeURL($key));
		if ($nren) {
			return $nren;
		}

		/* try the URL of the portal */
		$nren = self::getByURL(Input::sanitizeURL($key));
		if ($nren) {
			return $nren;
		}

		$nren = self::getByWAYF(Input::sanitizeURL($key));
		if ($nren) {
			return $nren;
		}

		$nren = self::getByID(Input::sanitizeID($key));
		if ($nren) {
			return $nren;
		}
		return false;
	} /* end getNREN() */

	/**
	 * getByID() return a decorated NREN based the database-ID
	 *
	 * @param	Integer $id the database-id
	 * @return	NREN|false the NREN or false if not found
	 * @access	public
	 */
	static function getByID($id)
	{
		if (!is_numeric($id)) {
			return false;
		}
		$query  = "SELECT idp_url from idp_map WHERE nren_id = ?";
		return self::getFromQuery($query, array('text'), array($id));
	}

	/**
	 * getByURL() return a decorated NREN from the portal's URL
	 *
	 * The URL is used by NRENs to provide a 'familiar' URL for the portal
	 * for the users. The URL portal.nren-a.org is then used by Confusa to
	 * find the corresponding NREN.
	 *
	 * Usage: when you want NREN-branding of the portal for unAuthN-users.
	 *
	 * @param	String $nren_url the URL of the service
	 * @return	NREN|false the NREN or false if not found
	 * @access	public
	 */
	function getByURL($nren_url)
	{
		if (!is_string($nren_url)) {
			return false;
		}
		$query  = "SELECT idp_url FROM idp_map idp LEFT JOIN nrens n ";
		$query .= "ON n.nren_id = idp.nren_id WHERE url = ?";
		return self::getFromQuery($query, array('text'), array($nren_url));
	} /* end getByURL */

	/**
	 * getByIdPURL() return a decorated NREN from it's IDP URL
	 *
	 * Getting a NREN by its IdP-URL is the most common way to identify a
	 * NREN, used throughout Confusa. This function should never be used
	 * directly, only when "guessing" all kinds of different identifiers for
	 * the NREN, such as different URLs.
	 *
	 * @param string $idp_url the URL of the idp
	 * @return NREN|false the NREN or false if not found
	 * @access private
	 */
	private static function getByIdPURL($idp_url)
	{
		if (!is_string($idp_url)) {
			return false;
		}

		$query = "SELECT idp_url FROM idp_map WHERE idp_url=?";
		return self::getFromQuery($query, array('text'), array($idp_url));
	} /* end getByIdPURL */

	/**
	 * getByWAYF() return a decorated NREN the WAYF URL
	 *
	 * @param	String $wayf_url
	 * @return	NREN|false the NREN or false if not found
	 * @access	public
	 */
	static function getByWAYF($wayf_url)
	{
		if (!is_string($wayf_url)) {
			return false;
		}
		$query  = "SELECT idp_url FROM idp_map idp LEFT JOIN nrens n ";
		$query .= "ON n.nren_id = idp.nren_id WHERE wayf_url = ?";
		return self::getFromQuery($query, array('text'), array($wayf_url));
	}


	/**
	 * getFromQuery() run the query and create a new NREN
	 *
	 * @param	String $query the query
	 * @param	Array  $params
	 * @param	Array  $data
	 * @access	private
	 */
	private function getFromQuery($query, $params, $data)
	{
		try {
			$res = MDB2Wrapper::execute($query, $params, $data);
			if (count($res) == 1) {
				if (array_key_exists('idp_url', $res[0])) {
					return new NREN($res[0]['idp_url']);
				}
			}
		} catch (DBStatementException $dbse) {
			Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ .
					  " problem with db-statement when finding NREN. " .
					  $dbse->getMessage());
		} catch (DBQueryException $dbqe) {
			Logger::log_event(LOG_ALERT, __FILE__ . ":" . __LINE__ .
			                  " Query-error when finding NREN. " .
					  $dbqe->getMessage());
		}
		return false;
	}
}
?>
