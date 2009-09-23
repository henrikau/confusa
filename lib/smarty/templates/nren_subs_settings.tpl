{if $person->isAdmin() && ($person->isNRENAdmin() || $person->isSubscriberAdmin())}

<div class="spacer"></div>
<fieldset>
{if $person->isNRENAdmin()}
<legend>NREN contact information</legend>
<br />
<p class="info">
Here you can define contact information for your NREN. Define an e-mail address
that is tied to a person or more persons that may be able to react if they receive notifications
from Confusa. For instance, Confusa might contact this address in case of critical errors
or to notify you of mass-revocation of certificates.
</p>

{elseif $person->isSubscriberAdmin()}
<legend>Subscriber contact information</legend>
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
</fieldset>

<br /><br />

<fieldset>
<legend>Language selection</legend>
<br />
<p class="info">
{if $person->isNRENAdmin()}
		Here you may select the default language of Confusa for the users within
		your NREN. Possible subscriber and user settings will override this.

{else}
		Here you may select the default language of Confusa for the users within
		your institution. Possible user settings will override this.
{/if}
</p>
<br />
<form method="post" action="">
		{html_options name="language" selected=$current_language output=$languages values=$language_codes}
		<input type="hidden" name="language_operation" value="update" />
		<input type="submit" value="Update" />
</form>
</fieldset>

{/if}
