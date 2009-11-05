<h3>about::confusa</h3>
<div class="spacer"></div>
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
<td style="width: 15em">{$cVersion}</td>
</tr>
<tr>
<td>Release codename:</td>
<td>{$cCodename}</td>
</tr>
<tr style="background-color: #eeeeee">
<td>DB schema (expected):</td>
<td>{$cExpSchema}</td>
</tr>
<tr>
<td>DB schema (found):</td>
<td>{$cFoundSchema}</td>
</tr>
</tbody>
</table>

<div class="spacer"></div>

{if $debug === true}
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
<td style="width: 15em">{$dPHPVersion}</td>
</tr>
<tr>
<td>Smarty version:</td>
<td>{$smarty.version}</td>
</tr>
<tr style="background-color: #eeeeee">
<td>MySQL version:</td>
<td>{$dMySQLVersion}</td>
</tr>
<tr>
<td>Server hostname:</td>
<td>{$dHostname}</td>
</tr>
</tbody>
</table>
{/if}
</div>
