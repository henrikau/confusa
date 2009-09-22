<?php
/* require_once 'logger.php'; */
require_once 'config.php';
require_once 'MDB2.php';
require_once 'logger.php';
require_once 'db_query.php';
require_once 'pw.php';

/* MDB2Wrapper
 *
 * Simple wrapper class for the MDB2-package.
 * The class will handle connecting to the database, retrieval of
 * username/passwords etc and return all results in an array.
 *
 * Hence, the user is left with a very simple call, just
 *
 * $res = MDB2Wrapper::execute(...)
 *
 * where $res will contain the .. result.
 *
 * Author: Henrik Austad <henrik.austad@uninett.no>
 */
class MDB2Wrapper
{
     private static $conn;
     private static $connCounter = 0;
     /* public static execute()
      *
      * params:
      * query:  prepared statement ready query. i.e. all variables you are going
      *         to test, leave as ?
      * types:  the type of the elements in the query.
      * data:   the actual data to put into the query
      *
      * Example:
      * To retrive the name of all users above a certain age, where the limit is
      * supplied by a webclient (through POST etc)
      *
      * $res = MDB2Wrapper::execute("SELECT name FROM userdb WHERE age > ?",
      *                                 array('int'),
      *                                 array('$_GET['age'])");
      */
     public static function execute($query, $types, $data)
     {

          if (!isset(MDB2Wrapper::$conn))
               MDB2Wrapper::create();
	  $results = array();

          $stmnt = MDB2Wrapper::$conn->prepare($query, $types, MDB2_PREPARE_RESULT);
          if (PEAR::isError($stmnt)) {
               Logger::log_event(LOG_NOTICE, "query failed "	. $stmnt->getMessage() . ".");
	       throw new DBStatementException("query failed: "	. $stmnt->getMessage() . ".");
          }

	  $res = $stmnt->execute($data);
          if (PEAR::isError($res)) {
		  $errorCode = create_pw(8);
		  $logMsg  = "[$errorCode] Query failed: $res->getMessage() - "  . $res->getUserInfo();
		  if (Config::get_config('debug')) {
			  $logMsg .= "[Debug]: " . $res->getDebugInfo();
		  }
		  Logger::log_event(LOG_NOTICE, $logMsg);
		  $stmnt->free();
		  throw new DBQueryException("Error-code: [$errorCode] " . $res->getMessage());
          }
          $stmnt->free();

		  $i = 0;
          while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
               $results[$i] = $row;
               $i = $i+1;
          }
	  MDB2Wrapper::$connCounter += 1;
          return $results;
     } /* end execute */

     /* at the moment, it just uses execute, without returning anything */
     public static function update($query, $types, $data) { MDB2Wrapper::execute($query, $types, $data); }

    /*
     * MySQL/the MDB2-MySQL wrapper seems to not support prepared
     * statements for batch INSERT operations. Hence we have to execute
     * such and maybe other statements as a query.
     */
     public static function query($query)
     {
       if (!isset(MDB2Wrapper::$conn)) {
         MDB2Wrapper::create();
       }

       $affected_rows = MDB2Wrapper::$conn->query($query);
       if (PEAR::isError($affected_rows)) {
         die("statement: " . $affected_rows->getMessage() . "<br />$query");
       }
     }

     /* create()
      *
      * This is where we create the connection (do'h...)
      *
      * It need the config.php (found in lib/) which in turn needs
      * confusa_config.php with the proper attributes set.
      * In short, you need config.php, config.php needs confusa_config.php, and
      * in confusa_config.php, you must have an array called $confusa_config,
      * with attributes on the form
      * 'mysql_username' => 'sample_username',
      * 'mysql...
      */
     private static function create()
     {
               $uname     = Config::get_config('mysql_username');
               $passwd    = Config::get_config('mysql_password');
               $host      = Config::get_config('mysql_host');
               $db        = Config::get_config('mysql_db');
               $dsn = "mysql://$uname:$passwd@$host/$db";
               $options = array(
                    'debug' => 2,
                    'result_buffering' => true
                    );
               MDB2Wrapper::$conn = MDB2::factory($dsn, $options);
               if (PEAR::isError(MDB2Wrapper::$conn)){
                    Logger::log_event(MDB2Wrapper::$conn->getMessage());
                    echo "Cannot connect to database: " . MDB2Wrapper::$conn->getMessge() . "<br>\n";
                    die(MDB2Wrapper::$conn->getMessage());
               }

          } /* end construct MDB2Wrapper */
     public static function getConnCounter()
     {
	     return MDB2Wrapper::$connCounter;
     }
} /* end MDB2Wrapper */
