{if $textual == 'yes'}
<div id="dn-section">
	{$person->getX509SubjectDN()}
</div>
{else}
<h3>This is what we know about you:</h3>
 <table class="small">
	<tr><td><b>Name:</b></td><td>{$person->getName()}</td></tr>
	<tr><td><b>eduPersonPrincipalName:</b></td><td>{$person->getEPPN()}</td></tr>
	<tr><td><b>CommonName in DN</b></td><td>{$person->getX509ValidCN()}</td></tr>
	<tr><td><b>email:</b></td><td>{$person->getEmail()}</td></tr>
	<tr><td><b>Country:</b></td><td>{$person->getCountry()}</td></tr>
	<tr><td><b>OrganizationalName:</b></td><td>{$person->getSubscriberOrgName()}</td></tr>
	<tr><td><b>Entitlement:</b></td><td>{$person->getEduPersonEntitlement()}</td></tr>
	<tr><td><b>IdP:</b></td><td>{$person->getIdP()}</td></tr>
	<tr><td><b>NREN:</b></td><td>{$person->get_nren()}</td></tr>
	<tr><td><b>Complete /DN:</b></td><td>{$person->getX509SubjectDN()}</td></tr>

	<tr><td></td><td></td></tr>

	<tr><td><b>Time left</b></td><td>{$timeLeft}</td></tr>
	<tr><td><b>Time since AuthN</b></td><td>{$timeSinceStart}</td></tr>
</table><br>
<hr />
We store very little information. What we do keep, is information about certificates issued, combined with the eduPersonPrincipalName
This is the DN in the certificate, and we <b>have</b> to store this.<br />
{/if}
