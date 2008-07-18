<?php
  /* get name of default log-file (in addition to syslog) */
  /* require_once(dirname(WEB_DIR).'/www/_include.php'); */
require_once("confusa_config.php");

/* SLCSLogger
 *
 * This logs to both syslog and a user defined log (in slcs_config.php in this example)
 *
 * Henrik Austad, June 2008, Uninett Sigma A/S
 */
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
               global $confusa_config;
               define_syslog_variables();

		/* no need to check if confusa_config exists, if not, framework should
		 * notify user and terminate */
		global $confusa_config;
		/* add this after the pri-test, as we don't want to  */
		if ($pri <= $confusa_config['syslog_min']) {
                     openlog("Confusa: ", LOG_PID | LOG_PERROR, LOG_LOCAL0);
                     syslog($pri, $message);
                     closelog();
		}
		/* log to normal file if within level. highest level is 0, increasing number
		 * is lower pri */
		if ($pri > $confusa_config['loglevel_min']) {
			echo "pri lower than loglevel_min <BR>\n";
			return;
		}
		switch($pri) {
		case LOG_DEBUG:
			$header .= "debug:";
			break;
		case LOG_INFO:
			$header .= "info:";
			break;
		case LOG_NOTICE:
			$header .= "notice:";
			break;
		case LOG_WARNING:
			$header .= "WARNING:";
			break;
		case LOG_ERR:
			$header .= " ERROR:";
			break;
		case LOG_CRIT:
			$header .= " -= CRITICAL =-";
			break;
		case LOG_ALERT:
			$header .= " -= [ ALERT ] =-";
			break;
		case LOG_EMERG:
			$header .= " EMERG EMERG EMERG";
			break;
		default:
			/* don't log things when you don't know how (un)important it is */
			echo "Don't know this loglevel ($pri). Please contact sys.developer<BR>\n";
			return;
			break;
		}
  
  
		/* enter into local logfile */
		$fd = fopen($confusa_config['default_log'], 'a');
		/* assemble line */
		$log_line = Logger::get_timestamp() . " (Confusa) " . $header . " " . $message . "\n";
                if ($confusa_config['debug'])
                     echo "Logline: " . $log_line . "<br>\n";
		fputs($fd, $log_line);
		/* echo $log_line . "<BR>\n"; */
		@fclose($fd);
  
	}

	/* create a timestamp to put in the normal log */
	static function get_timestamp() {
		$timestamp = strftime("%Y %b %d %H:%M:%S");
		return $timestamp;
	}
} /* end Logger */
