<?php
/* require_once 'logger.php'; */
require_once 'config.php';
require_once 'MDB2.php';
require_once 'logger.php';
require_once 'db_query.php';
require_once 'pw.php';

/**
 * MDB2Wrapper	Simple wrapper class for the MDB2-package.
 *
 * This is a wrapper to the databasewrapper MDB2. The intention is to completely
 * remove the need for finding username and password, creating statement etc.
 *
 * The goal is to shrink a databasequery down to a *single* line of code elsewhere.
 *
 * @author     Henrik Austad <henrik.austad@uninett.no>
 */
class MDB2Wrapper
{
     private static $conn;
     private static $connCounter = 0;

     /**
      * execute() execute a sql-query.
      *
      * A standard sql-query wrapper using prepared statements to minimize the
      * danger for SQL-injection attacks.
      *
      * It supports both named and unnamed placeholders.
      *
      * Named placeholders:
      *		$data = araray();
      *		$data['id'] = 1;
      *		$res = MDB2Wrapper::execute('SELECT * FROM table WHERE id=:id',
      *					    null,
      *					    $data);
      *
      * Unnamed placeholders:
      *		$res = MDB2Wrapper::execute('SELECT * FROM table WHERE id==',
      *					    array('text'),
      *					    array(1));
      *
      * @param String		$query	The query to execute
      * @param Array|null	$types	The datatypes that goes with the query
      * @param Array|null	$data	The data to pass along with the query.
      * @param Boolean		$update	Flag to indicate if the query is an
      *					update or not. If true, no data will be
      *					fetched from the resultset.
      */
     public static function execute($query, $types, $data, $update = false)
     {

          if (!isset(MDB2Wrapper::$conn))
               MDB2Wrapper::create();

          $stmnt = MDB2Wrapper::$conn->prepare($query, $types, MDB2_PREPARE_RESULT);
          if (PEAR::isError($stmnt)) {
               Logger::log_event(LOG_NOTICE, "query failed "	. $stmnt->getMessage() . ".");
	       throw new DBStatementException("query failed: "	. $stmnt->getMessage() . ".");
          }

	  $res = $stmnt->execute($data);
          if (PEAR::isError($res)) {
		  $errorCode = create_pw(8);
		  $logMsg  = "[$errorCode] Query failed: " . $res->getMessage() . " - ". $res->getUserInfo();
		  if (Config::get_config('debug')) {
			  $logMsg .= "[Debug]: " . $res->getDebugInfo();
		  }
		  Logger::log_event(LOG_NOTICE, $logMsg);
		  $stmnt->free();
		  throw new DBQueryException("Error-code: [$errorCode] " . $res->getMessage());
          }
          $stmnt->free();

	  if ($update) {
		  return;
	  }

	  $results = array();
	  $i = 0;
	  while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		  $results[$i] = $row;
		  $i = $i+1;
	  }
	  MDB2Wrapper::$connCounter += 1;
          return $results;
     } /* end execute */

     /**
      * update()  run an update-query.
      *
      * Since this function will only update, nothing is returned. To avoid
      * adding a lot of code, we hook into execute, and set the update-flag to
      * true.
      *
      * @param String $query the update-query
      * @param Array|null $types array of types to pass along
      * @param Array|null $data the data associated with the query.
      *
      * @return void
      * @access public
      */
     public static function update($query, $types, $data)
     {
	     MDB2Wrapper::execute($query, $types, $data, true);
     }


     /**
      * MDB2Wrapper::query()
      *
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

     /**
      * MDB2Wrapper::create() create the connection (connect and initialize)
      *
      * This function will retrieve the database-credentials from the
      * config-file. From this, it will connect tot he database and store the
      * connection so the other functions can use it.
      *
      * At the moment, this is hard-coded to mysql (see the naming
      * below), but if we should ever change to another database, this is the
      * place to add the logic).
      *
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

     } /* end create() */

     /**
      * getConnCounter() return the number of times the connection has been used
      *
      * When we are using the database-layer, we want to know how hard we hammer
      * the database. This function will return the number of queries submitted
      * to the layer.
      *
      * @return Int $connCounter the counter for how many times the wrapper has
      *				 been used.
      */
     public static function getConnCounter()
     {
	     return MDB2Wrapper::$connCounter;
     } /* end getConnCounter */

} /* end MDB2Wrapper */
