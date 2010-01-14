{if is_null($person) || !$person->isAuth()}
	<h2>{$l10n_heading_logout}</h2>
	<br />
	{$l10n_text_logoutreturn1} <a href="index.php">{$l10n_link_start}</a>
{else}
	<h2>Logout failed</h2>
	<br />
	Something went wrong with logging you out of Confusa.
	Seems like you are still authenticated...<br /><br />
	<b>Under normal conditions, you should never see
	this message! Please contact an administrator.</b>
{/if}
