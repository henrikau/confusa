{if $textual == 'yes'}
<div id="dn-section">
	{$person->getX509SubjectDN()|escape}
</div>
{else}
<h3>This is what we know about you:</h3>
 <table class="small">
	<tr><td><b>Name:</b></td><td>{$person->getName()|escape}</td></tr>
	<tr><td><b>eduPersonPrincipalName:</b></td><td>{$person->getEPPN()|escape}</td></tr>
	<tr><td><b>CommonName in DN</b></td><td>{$person->getX509ValidCN()|escape}</td></tr>
	<tr><td><b>email:</b></td><td>{$person->getEmail()|escape}</td></tr>
	<tr><td><b>Country:</b></td><td>{$nren->getCountry()|escape}</td></tr>

	<tr><td><b>OrganizationalName:</b></td>
	  <td>
	    {if $subscriber}
	    {$subscriber->getOrgName()|escape}
	    {/if}
	  </td>
	</tr>
	<tr><td><b>Organizational Identifier:</b></td>
	  <td>
	    {if $subscriber}
	    {$subscriber->getIdPName()|escape}
	    {/if}
	  </td>
	</tr>
	<tr><td><b>Entitlement:</b></td><td>{$person->getEntitlement()|escape}</td></tr>
	<tr><td><b>NREN:</b></td><td>{$person->getNREN()|escape}</td></tr>
	<tr><td><b>Complete /DN:</b></td><td>{$person->getX509SubjectDN()|escape}</td></tr>

	<tr><td></td><td></td></tr>

	<tr><td><b>Time left</b></td><td>{$timeLeft|escape}</td></tr>
        <tr><td><b>Time since AuthN</b></td><td>{$timeSinceStart|escape}</td></tr>
</table><br />
<hr />

We store very little information. What we do keep, is information about
certificates issued, combined with the <a
href="http://rnd.feide.no/attribute/edupersonprincipalname">eduPersonPrincipalName</a>. This
is part of the DN in the certificate (it is the unique identifier for
<i>you</i> in the namespace of your organization), and we <b>have</b> to
store this.<br />
{/if}
