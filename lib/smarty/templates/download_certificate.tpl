<h2>Certificate Download Area</h2>

{if empty($certList)}
No certificates in database
{else}
<table>
	<tr>
		<th colspan="2"></th>
		<th>Expires(from DB)</th>
		<th></th>
		<th>AuthToken</th>
		<th>Owner</th>
	</tr>
	{foreach from=$certList item=cert}
	<tr>
		<td></td>
	</tr>
</table>
