<?php
require_once('logger.php');
require_once('config.php');
require_once('MDB2.php');

class MDB2Wrapper
{
     private $conn;
     public function __construct() 
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
               $this->conn =& MDB2::factory($dsn, $options);
               if (PEAR::isError($this->conn)){
                    Logger::log_event($this->conn->getMessage());
                    echo "Cannot connect to database: " . $this->conn->getMessge() . "<br>\n";
                    die($this->conn->getMessage());
               }

          } /* end construct MDB2Wrapper */
     public function __destruct()
          {
               ;
          }

     public function query_int($query, $in_nmb)
          {
               return $this->execute($query, array('integer'), array($in_nmb));
          }

     public function query_string($query, $in_string)
          {
               return $this->execute($query, array('text'), array($in_string));
          }
     private function execute($query, $types, $data)
          {
               $stmnt   = $this->conn->prepare($query, $types, MDB2_PREPARE_RESULT);
               $res     = $stmnt->execute($data);
               if (PEAR::isError($res)) {
                    Logger::log_event(LOG_NOTICE, "Query failed . " . $res->getMessage());
                    echo "could not execute query (".$query.")<br>\n";
                    die($res->getMessage());
               }
               $stmnt->free();
               while($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                    $results[] = $row;
               }
               return $results;
          }
}
