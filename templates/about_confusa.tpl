<h3>about::confusa</h3>
<div class="spacer"></div>
{if $person->inAdminMode()}
<h4>{$l10n_heading_admininfo}</h4>
<div style="margin-bottom: 2em">
<table style="border-style: dashed; border-width: 0.1em">
<thead>
<tr>
<th>Confusa information</th>
</tr>
</thead>
<tbody>
<tr style="background-color: #eeeeee">
<td style="width: 15em">Confusa version:</td>
<td style="width: 15em">{$cVersion|escape}</td>
</tr>
<tr>
<td>Release codename:</td>
<td>{$cCodename|escape}</td>
</tr>
</tbody>
</table>

<div class="spacer"></div>

<table style="border-style: dashed; border-width: 0.1em">
<thead>
<tr>
<th style="width: 15em">System information</th>
</tr>
</thead>
<tbody>
<tr style="background-color: #eeeeee">
<td>PHP version:</td>
<td style="width: 15em">{$dPHPVersion|escape}</td>
</tr>
<tr>
<td>Smarty version:</td>
<td>{$smarty.version|escape}</td>
</tr>
<tr style="background-color: #eeeeee">
<td>MySQL version:</td>
<td>{$dMySQLVersion|escape}</td>
</tr>
<tr>
<td>Server hostname:</td>
<td>{$dHostname|escape}</td>
</tr>
</tbody>
</table>
</div>

<h4>{$l10n_heading_credits}</h4>
{/if}

	<p class="info">{$l10n_infotext_thanks1}</p>

	<h4>{$l10n_heading_authentication}</h4>

	<p class="info"><a href="http://rnd.feide.no/simplesamlphp" target="_blank">simplesamlphp</a> {$l10n_infotext_thanks2}</p>

	<h4>{$l10n_heading_icons}</h4>

	<p class="info">{$l10n_infotext_thanks3}</p>

	{* please include your very own tailored HTML-credit-file here, if you are
	   not operating the portal from the university of Tilburg *}

	<h4>{$l10n_heading_software}</h4>

	<p class="info">{$l10n_infotext_thanks4}</p>
	<ul class="info">
		<li>PHP</li>
		<li>Smarty templating engine</li>
		<li>MySQL</li>
		<li>curl</li>
	</ul>

	{* add your own operational credits here, if you are a portal instance operator *}
	{if isset($op_creds)}
		<h4>{$l10n_heading_operations}</h4>
		<p>{$l10n_infotext_thanks5}</p>
		{$op_creds}
	{/if}
