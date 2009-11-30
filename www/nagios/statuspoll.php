<?php
require_once '../confusa_include.php';
require_once 'config.php';
require_once 'confusa_constants.php';

try {
	require_once Config::get_config('smarty_path') . 'Smarty.class.php';
} catch (KeyNotFoundException $knfe) {
	die("Cannot load smarty, smarty_path not set!");
}

/**
 * StatusPoll - graphical interface reporting to human caller as well as nagios,
 * if a certain event exceeded a given log-level (EMERG, CRIT, ALERT, ERR, WARN,
 * NOTICE) is in the log. A non-visible text constant, being one of
 *
 * NAGIOS_CONST_NO_ERROR_ABOVE_LOGLEVEL
 * NAGIOS_CONST_ERROR_ABOVE_LOGLEVEL
 *
 * is exported for nagios and a visual representation and
 * log entry list for the human reader.
 *
 * @author Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class StatusPoll
{

	private $tpl;
	private $logErrors;
	private $ALLOWED_LEVELS = array('notice', 'warning', 'err', 'alert',
	                                'emerg', 'crit');

	public function __construct()
	{
		$this->tpl	= new Smarty();
		$this->tpl->template_dir= Config::get_config('install_path') .
		                          'lib/smarty/templates';
		$this->tpl->compile_dir	= ConfusaConstants::$SMARTY_TEMPLATES_C;
		$this->tpl->config_dir	= Config::get_config('install_path') .
		                          'lib/smarty/configs';
		$this->tpl->cache_dir	= ConfusaConstants::$SMARTY_CACHE;

		$this->logErrors = array();
	}

	/**
	 * Assign all the smarty variables to the template. This is roughly
	 * equivalent to pre_process in the Framework-parts of Confusa.
	 *
	 * The variables are 'logLevelReached' - boolean indicating if the log level
	 * was reached, 'logLevel' - the level itself and 'logErrors' - a
	 * comprehensive list of the logged errors.
	 */
	public function assignVars()
	{
		$confusaLog = Config::get_config('default_log');
		$logLevelReached = false;

		if (file_exists($confusaLog) === false) {
			/* let's be optimistic */
			$this->tpl->assign('logLevelReached', false);
			$this->tpl->assign('logLevel', $_GET['level']);
			return;
		}

		if (isset($_GET['level'])) {
			if (array_search($_GET['level'], $this->ALLOWED_LEVELS) === false) {
				$this->tpl->assign('logLevelReached', false);
				$this->tpl->assign('logLevel', "unknown level");
				return;
			}

			$level = $_GET['level'];
		} else {
			$level = "crit";
		}

		$lineregex = "";

		switch($level) {
		case 'emerg':
			$emerg = ConfusaConstants::$LOG_HEADER_EMERG;
			$lineregex = "\".*(Confusa) $emerg.*\"";
			break;
		case 'alert':
			$emerg = ConfusaConstants::$LOG_HEADER_EMERG;
			$alert = ConfusaConstants::$LOG_HEADER_ALERT;
			$lineregex = "\".*(Confusa) $emerg.*\|.*(Confusa) $alert.*\"";
			break;
		case 'crit':
			$emerg = ConfusaConstants::$LOG_HEADER_EMERG;
			$alert = ConfusaConstants::$LOG_HEADER_ALERT;
			$crit  = ConfusaConstants::$LOG_HEADER_CRIT;
			$lineregex = "\".*(Confusa) $emerg.*\|.*(Confusa) $alert.*\|" .
			              ".*(Confusa) $crit.*\"";
			break;
		case 'err':
			$emerg = ConfusaConstants::$LOG_HEADER_EMERG;
			$alert = ConfusaConstants::$LOG_HEADER_ALERT;
			$crit  = ConfusaConstants::$LOG_HEADER_CRIT;
			$err   = ConfusaConstants::$LOG_HEADER_ERR;
			$lineregex = "\".*(Confusa) $emerg.*\|.*(Confusa) $alert.*\|" .
			              ".*(Confusa) $crit.*\|.*(Confusa) $err.*\"";
			break;
		case 'warning':
			$emerg   = ConfusaConstants::$LOG_HEADER_EMERG;
			$alert   = ConfusaConstants::$LOG_HEADER_ALERT;
			$crit    = ConfusaConstants::$LOG_HEADER_CRIT;
			$err     = ConfusaConstants::$LOG_HEADER_ERR;
			$warning = ConfusaConstants::$LOG_HEADER_WARNING;
			$lineregex = "\".*(Confusa) $emerg.*\|.*(Confusa) $alert.*\|" .
			              ".*(Confusa) $crit.*\|.*(Confusa) $err.*\|" .
			              ".*(Confusa) $warning.*\"";
			break;
		/* won't poll the status below notice */
		case 'notice':
		default:
			$emerg   = ConfusaConstants::$LOG_HEADER_EMERG;
			$alert   = ConfusaConstants::$LOG_HEADER_ALERT;
			$crit    = ConfusaConstants::$LOG_HEADER_CRIT;
			$err     = ConfusaConstants::$LOG_HEADER_ERR;
			$warning = ConfusaConstants::$LOG_HEADER_WARNING;
			$notice  = ConfusaConstants::$LOG_HEADER_NOTICE;
			$lineregex = "\".*(Confusa) $emerg.*\|.*(Confusa) $alert.*\|" .
			              ".*(Confusa) $crit.*\|.*(Confusa) $err.*\|" .
			              ".*(Confusa) $warning.*\|.*(Confusa) $notice.*\"";
			$level = "notice";
			break;
		}

		$this->tpl->assign('logLevel', $level);
		$grep = "grep " . $lineregex;

		/* filter the data through the regex while reading it */
		$handle = popen($grep . " " . $confusaLog, "r");
		if ($handle) {
			while (!feof($handle)) {
				$line = fgets($handle, 4096);
				if (strlen($line) > 0) {
					$logLevelReached = true;
					$this->logErrors[] = $line;
				}
			}

			pclose($handle);
		}

		$this->tpl->assign('logErrors', $this->logErrors);
		$this->tpl->assign('logLevelReached', $logLevelReached);
	}

	/**
	 * Display the statuspoll-template
	 */
	public function process()
	{
		$this->tpl->display('nagios/statuspoll.tpl');
	}
}


$sp = new StatusPoll();
$sp->assignVars();
$sp->process();


?>
