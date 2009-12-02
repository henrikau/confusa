<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Nagios status polling page</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
</head>
<body>

<div style="width: 50%">

{if isset($generalErrors) && $generalErrors === true}
	<p style="color: red">
	A general error occured when trying to compile the status information!
	</p>

	<pre style="color: #333333; border: 1pt dashed">
		{$errorMessage}
	</pre>

	<p>
	Maybe Confusa is not configured properly. If you are an administrator,
	please try to figure out if Confusa can connect to the DB, read the
	configuration file etc. The fact this status page does not work properly
	indicates that with a high probability other parts of Confusa won't work properly
	either.
	</p>

{else}
	{if isset($logLevelReached) && $logLevelReached === false}

	<p>
	No error with greater or equal severity than Confusa's configured critical log-level '<strong>{$logLevel|escape}</strong>' found!
	</p>

	<div style="display: none">
		NAGIOS_CONST_NO_ERROR_ABOVE_LOGLEVEL
	</div>

	{else}

	<p style="color: red">
	Errors with severity greater or equal than Confusa's configured critical log-level <strong>'{$logLevel|escape}'</strong> found!
	</p>

	<div style="display: none">
		NAGIOS_CONST_ERROR_ABOVE_LOGLEVEL
	</div>

	<p>A detailed list of all the log errors:</p>
	<div style="border: 1px dashed">
	<ol style="margin-left: 20px">
	{foreach from=$logErrors item=logError}
		{cycle values='background-color: #ffffff,background-color: #cccccc' assign=logEntryStyle}
		<li style="{$logEntryStyle}">{$logError|escape}</li>
	{/foreach}
	</ol>
	</div>
	{/if}
{/if}

</div>
</body>
</html>
