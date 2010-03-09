<?php
require_once '../confusa_include.php';
require_once 'Config.php';
require_once 'confusa_constants.php';
require_once 'MDB2Wrapper.php';

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
	private $earliestTimestamp;
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

	public function getCriticalErrors()
	{
		$query = "SELECT error_date, log_msg FROM critical_errors WHERE " .
		         "is_resolved = false";

		try {
			$res = MDB2Wrapper::execute($query, null, null);
		} catch (ConfusaGenException $e) {
			$this->tpl->assign('generalErrors', true);
			$this->tpl->assign('errorMessage', $e->getMessage());
			return false;
		}

		foreach ($res as $row) {
			$this->logErrors[] = $row['error_date'] . " " . $row['log_msg'];
		}

		return true;
	} /* end getCriticalErrors */

	/**
	 * Assign all the smarty variables to the template. This is roughly
	 * equivalent to pre_process in the Framework-parts of Confusa.
	 *
	 * The variables are 'logLevelReached' - boolean indicating if the log level
	 * was reached and 'logErrors' - a comprehensive list of the logged errors.
	 */
	public function assignVars()
	{
		if (count($this->logErrors) > 0) {
			$this->tpl->assign('logErrors', $this->logErrors);
			$this->tpl->assign('logLevelReached', true);
		} else {
			$this->tpl->assign('logLevelReached', false);
		}

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
if ($sp->getCriticalErrors()) {
	$sp->assignVars();
}
$sp->process();


?>
