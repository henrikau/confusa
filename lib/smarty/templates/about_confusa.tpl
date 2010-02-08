<h3>about::confusa</h3>
<div class="spacer"></div>
{if $person->inAdminMode()}
<div>
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

{if isset($debug) && $debug === true}
{* debug information *}
<table style="border-style: dashed; border-width: 0.1em">
<thead>
<tr>
<th style="width: 15em">Debug information</th>
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
{/if}
</div>
{else}

	<p>Confusa </p>

	<h4>Authentication</h4>

	<p>The portal development team would like to thank the creators of
	<a href="http://rnd.feide.no/simplesamlphp" target="_blank">simplesamlphp</a>
	which is the authentication backend of Confusa.</p>

	<h4>Icons</h4>
	 
	<h4>Server components</h4>
	<div>
		<ul style="margin-left: 3em">
			<li>PHP</li>
			<li>MySQL</li>
		</ul>
	</div>
{/if}
