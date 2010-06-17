<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>OAuth consent</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<link rel="stylesheet" href="../css/confusa2.css" type ="text/css" />
</head>
<body>

<div id="site" style="min-height: 500px">

<h1 style="margin: 0em 2em 1em 2em">{$l10n_heading_consent}</h1>

<div style="width: 50%">

<p>{$l10n_infotext_consent1}</p>

</div>

<table style="padding: 2em 0 2em 0">
<tr>
	<td style="text-align: right; padding-right: 2em">{$l10n_label_consumername}</td>
	<td>{$consumer_name}</td>
</tr>
<tr>
	<td style="text-align: right; padding-right: 2em">{$l10n_label_consumerdescription}</td>
	<td>{$consumer_description}</td>
</tr>
<tr>
	<td style="text-align: right; padding-right: 2em">{$l10n_label_serviceid}</td>
	<td>{$consumer_key}</td>
</tr>
<tr>
	<td style="text-align: right; padding-right: 2em">{$l10n_label_accessduration}</td>
	<td>{$access_duration} {$l10n_text_minutes}</td>
</table>

<div style="width: 50%">

<p>{$l10n_infotext_consent2} "{$consumer_name}" {$l10n_infotext_consent3}</p>

<p>{$l10n_infotext_consent4} <a href="mailto:{$help_email}">{$l10n_infotext_consent5}</a>.
{$l10n_infotext_consent6}
</p>

</div>

<div style="padding: 2em 0 0 2em; align: center">
	<div style="width: 10em; float:left">
	<form action="consent" method="post">
		<input type="hidden" name="consent_val" value="{$consent_val}" />
		<input type="submit" name="consent" value="{$l10n_button_consent}" />
	</form>
	</div>

	<div style="width: 10em; float: left">
	<form action="noconsent" method="get">
		<input type="submit" name="noconsent" value="{$l10n_button_noconsent}" />
	</form>
	</div>
</div>
</div>
</body>
</html>
