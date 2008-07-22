<?php
require_once('confusa_config.php');
require_once('logger.php');
require_once('MDB2.php');

$sql_conn = null;
$use_pear = false;

function get_sql_conn() {
	global $sql_conn;
        global $use_pear;
        if ($use_pear) {
             $uname     = Config::get_config('mysql_username');
             $passwd    = Config::get_config('mysql_password');
             $host      = Config::get_config('mysql_host');
             $db        = Config::get_config('mysql_db');
             $dsn = "mysql://$uname:$passwd@$host/$db";
             $options = array(
                  'debug' => 2,
                  'result_buffering' => true
                  );
             $sql_conn =& MDB2::factory($dsn, $options);
             if (PEAR::isError($sql_conn)){
                  Logger::log_event($sql_conn->getMessage());
                  die($sql_conn->getMessage());
             }
        }
        else {
             if (!isset($sql_conn)) {
                  $sql_conn = new MySQLConn();
             }
        }
	return $sql_conn;
}

function use_pear($use)
{
     global $use_pear;
     $use_pear = $use;
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
		$result = @mysql_query($query)
                     or die(Logger::log_event(LOG_ERR, "Query failed ($query) Contact adminstrator.") . "Query failed (and logged)<br>\n");
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
              require_once('config.php');
              parent::__construct(Config::get_config('mysql_username'),
                                  Config::get_config('mysql_password'),
                                  Config::get_config('mysql_host'),
                                  Config::get_config('mysql_db'),
                                  Config::get_config('mysql_default_table'));
              
        }
    function __destruct()
        {
        parent::__destruct();
        }

    } /* end class MySQLConn*/


?>
