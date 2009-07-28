{if $textual == 'yes'}
<div id="dn-section">
	{$person->get_complete_dn()}
</div>
{else}
<h3>This is what we know about you:</h3>
 <table class="small">
	<tr><td><b>Name:</b></td><td>{$person->get_name()}</td></tr>
	<tr><td><b>eduPersonPrincipalName:</b></td><td>{$person->get_common_name()}</td></tr>
	<tr><td><b>CommonName in DN</b></td><td>{$person->get_valid_cn()}</td></tr>
	<tr><td><b>email:</b></td><td>{$person->get_email()}</td></tr>
	<tr><td><b>Country:</b></td><td>{$person->get_country()}</td></tr>
	<tr><td><b>OrganizationalName:</b></td><td>{$person->get_orgname()}</td></tr>
	<tr><td><b>Entitlement:</b></td><td>{$person->get_entitlement()}</td></tr>
	<tr><td><b>IdP:</b></td><td>{$person->get_idp()}</td></tr>
	<tr><td><b>NREN:</b></td><td>{$person->get_nren()}</td></tr>
	<tr><td><b>Complete /DN:</b></td><td>{$person->get_complete_dn()}</td></tr>
</table><br>
<hr />
We store very little information. What we do keep, is information about certificates issued, combined with the eduPersonPrincipalName
This is the DN in the certificate, and we <b>have</b> to store this.<br />
{/if}
