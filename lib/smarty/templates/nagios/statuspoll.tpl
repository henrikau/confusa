<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Nagios status polling page</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
</head>
<body>
{if isset($logLevelReached) && $logLevelReached === false}

<p>
Log-level '<strong>{$logLevel|escape}</strong>' not exceeded since last log-rotation.
</p>

<div style="display: none">
	NAGIOS_CONST_NO_ERROR_ABOVE_LOGLEVEL
</div>

{else}

<p style="color: red">Errors above the given loglevel <strong>'{$logLevel|escape}'</strong> happened.</p>

<div style="display: none">
	NAGIOS_CONST_ERROR_ABOVE_LOGLEVEL
</div>

<p>A detailed list of all the log errors:</p>
<div style="width: 40%; border: 1px dashed">
<ol style="margin-left: 20px">
{foreach from=$logErrors item=logError}
	{cycle values='background-color: #ffffff,background-color: #cccccc' assign=logEntryStyle}
	<li style="{$logEntryStyle}">{$logError|escape}</li>
{/foreach}
</ol>
</div>
{/if}
</body>
</html>
