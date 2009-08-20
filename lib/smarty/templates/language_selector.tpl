{if $person->isAdmin() && $person->inAdminMode()}
<h3>Language selection</h3>
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

{/if}


