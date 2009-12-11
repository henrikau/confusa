<?php
/* Logger
 *
 * This logs to both syslog and a user defined log
 *	This is set by the config-variable default_log in
 *	config/confosa_config.php
 *
 * By adjusting the levels to log, different levels of logging can be set for
 * syslog and the local log. This is done by tuning
 *	- syslog_min (for syslog)
 *	- loglevel_min (for user-defined log)
 *
 * Henrik Austad, June 2008, Uninett Sigma A/S
 */


  /* get name of default log-file (in addition to syslog)
   * require_once(dirname(WEB_DIR).'/www/_include.php'); */
require_once 'config.php';
require_once 'confusa_constants.php';
class Logger {
/* log_event
 *
 * Uses the both syslog and custom-log for logging. It can (in a future release)
 * configured to split events into different logs etc.
 *
 * At the moment, the logger logs identical data (though, with different header)
 * to both syslog and to default-log (specified in confusa_config.php).
 *
 * It uses the same log-levels as syslog, but if the level is outside the
 * defined level (ie, LOG_DEBUG will be dropped if loglevel_min is LOG_NOTICE)
 *
 * From php.net:
 *
 * syslog() Priorities (in descending order)
 * Constant		Description
 * LOG_EMERG		system is unusable
 * LOG_ALERT		action must be taken immediately
 * LOG_CRIT		critical conditions
 * LOG_ERR		error conditions
 * LOG_WARNING	warning conditions
 * LOG_NOTICE	normal, but significant, condition
 * LOG_INFO		informational message
 * LOG_DEBUG		debug-level message
 */
     static function log_event($pri, $message)
     {

		/* add this after the pri-test, as we don't want to  */
		if ($pri <= Config::get_config('syslog_min')) {
                     openlog("Confusa: ", LOG_PID | LOG_PERROR, LOG_LOCAL0);
                     syslog((int)$pri, $message);
                     closelog();
		}

	       /* open local logfile */
	       $fd = @fopen(Config::get_config('default_log'), 'a');
	       if (!$fd) {
		       openlog("Confusa: ", LOG_PID | LOG_PERROR, LOG_LOCAL0);
		       syslog(LOG_EMERG, "Confusa: cannot open secondary logfile (" . Config::get_config('default_log') . ")");
		       closelog();
		       return;
	       }
		/* log to normal file if within level. highest level is 0, increasing number
		 * is lower pri */
		if ($pri > Config::get_config('loglevel_min')) {
			fclose($fd);
			return;
		}

		/* The prefix for the log-messages that will be place in syslog
		 * and confusa.log */
		$header = "";

		switch($pri) {
		case LOG_DEBUG:
			$header .= ConfusaConstants::$LOG_HEADER_DEBUG;
			break;
		case LOG_INFO:
			$header .= ConfusaConstants::$LOG_HEADER_INFO;
			break;
		case LOG_NOTICE:
			$header .= ConfusaConstants::$LOG_HEADER_NOTICE;
			break;
		case LOG_WARNING:
			$header .= ConfusaConstants::$LOG_HEADER_WARNING;
			break;
		case LOG_ERR:
			$header .= ConfusaConstants::$LOG_HEADER_ERR;
			break;
		case LOG_CRIT:
			$header .= ConfusaConstants::$LOG_HEADER_CRIT;
			break;
		case LOG_ALERT:
			$header .= ConfusaConstants::$LOG_HEADER_ALERT;
			break;
		case LOG_EMERG:
			$header .= ConfusaConstants::$LOG_HEADER_EMERG;
			break;
		default:
			/* don't log things when you don't know how (un)important it is */
			Framework::error_output("Don't know this loglevel ($pri). Please contact sys.developer");
			return;
			break;
		}
		/* assemble line and enter into local log */
		$timestamp = Logger::get_timestamp();
		$log_body = " (Confusa) " . $header . " " . $message;
		$log_line = $timestamp . $log_body . "\n";
		fputs($fd, $log_line);
		@fclose($fd);

		/* insert a critical error into the DB, if possible */
		if ($pri <= Config::get_config('loglevel_fail')) {
			Logger::insertCriticalErrorIntoDB($pri, $log_body);
		}
	}

	/* create a timestamp to put in the normal log */
	static function get_timestamp() {
		$timestamp = strftime("%Y %b %d %H:%M:%S");
		return $timestamp;
	}

	/**
	 * An error considered critical for Confusa's execution has happened, try
	 * to insert it into the DB, so error reporting tools and admins can handle
	 * it.
	 *
	 * @param $log_level const integer The log level (EMERG, CRIT...) of the
	 *                                 log-event
	 * @param $log_body  string        The log message itself
	 */
	static function insertCriticalErrorIntoDB($log_level, $log_body)
	{
		include_once 'mdb2_wrapper.php';
		include_once 'confusa_gen.php';

		$query = "INSERT INTO critical_errors(error_date, error_level, log_msg) ";
		$query .= "VALUES(current_timestamp,?,?)";

		try {
			$res = @MDB2Wrapper::update($query,
			                            array('text','text'),
			                            array($log_level, $log_body));
		} catch (ConfusaGenException $e) {
			/* log the exception... no, wait... */
		}
	} /* end insertCriticalErrorIntoDB */
} /* end Logger */
