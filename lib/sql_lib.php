<?php
require_once('confusa_config.php');
require_once('logger.php');

$sql_conn = null;
function get_sql_conn() {
	global $sql_conn;
	if (!isset($sql_conn)) {
		$sql_conn = new MySQLConn();
	}
	return $sql_conn;
}


class SqlConn {
    private $conn;
    private $username;
    private $password;
    private $host;
    private $db;
    private $default_table;

    function __construct($un, $pw, $host, $db, $dtable)
        {
        /* get vars */
        $this->username = $un;
        $this->password = $pw;
        $this->host = $host;
        $this->db = $db;
        $this->default_table = $dtable;

        /* setup sql, choose db */
        $this->login();
        $this->select_db($this->db);
        } /* end __construct SqlConn */

    /* destructor */
    function __destruct() 
        {
        $this->logout();
        } /* end __destruct SqlConn */

    public function select_db($db) 
        {
		mysql_select_db($db) or die('Could not select database');
        } /* end select_db */

    /* executes a query (returns the resultset, or, rather, the result-resource */
    public function execute($query)
        {
        $result = null;
        if(!empty($query)) { 
             Logger::log_event(LOG_DEBUG, "Running query: " . $query);
		$result = @mysql_query($query)
			or die(mysql_error() . "Query failed. Contact adminstrator.");
        }
        else {
            die(__FILE__ . " -> Cannot execute empty query!");
            }
        return $result;
        } /* end execute */

    /* pure update of database. Returns a boolean to indicate success or not. */
    public function update($update_query)
        {
        $this->execute($update_query);
        } /* end update */


    public function get_default_table() 
    {
        return $this->default_table;
        } /* end get_default_table */

    private function login() {
        $this->conn = mysql_pconnect($this->host, $this->username, $this->password) or 
            die("Could not connect to database " . $this->host . "!");
        mysql_select_db($this->db) or die('Could not select database' . $this->db . ".");
        } /* end login */

    private function logout() 
        {
            if ($this->conn) {
                mysql_close($this->conn);
                $this->conn = null;
            } /* endif */
        } /* end logout */

    }     /* end class SqlConn */

class MySQLConn extends SqlConn 
    {
    function __construct()
        {
             global $confusa_config;
             parent::__construct($confusa_config['mysql_username'],
			   $confusa_config['mysql_password'],
			   $confusa_config['mysql_host'],
			   $confusa_config['mysql_db'],
			   $confusa_config['mysql_default_table']);
        }
    function __destruct()
        {
        parent::__destruct();
        }

    } /* end class MySQLConn*/


?>
