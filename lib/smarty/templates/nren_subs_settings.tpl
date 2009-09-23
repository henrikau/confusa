{if $person->isAdmin() && ($person->isNRENAdmin() || $person->isSubscriberAdmin())}

{if $person->isNRENAdmin()}
<h3>NREN contact information</h3>
<br />
<p class="info">
Here you can define contact information for your NREN. Define an e-mail address
that is tied to a person or more persons that may be able to react if they receive notifications
from Confusa. For instance, Confusa might contact this address in case of critical errors
or to notify you of mass-revocation of certificates.
</p>

{elseif $person->isSubscriberAdmin()}
<h3>Subscriber contact information</h3>
<br />
<p class="info">
Here you can define contact information for your subscriber. Define an e-mail address
that is tied to a person or more persons that may be able to react if they receive notifications
from Confusa. For instance, Confusa might contact this address in case of critical errors or to
notify you of mass-revocation of certificates.
</p>

{/if}
<br />
Contact-email:
<form method="post" action="">
	<input type="hidden" name="setting" value="contact" />
	<input type="text" name="contact" value="{$contact}" />
	<input type="submit" value="Update" />
</form>

{/if}
