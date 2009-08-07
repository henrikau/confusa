{if is_null($person) || !$person->isAuth()}
	<h2>You have been logged out of Confusa</h2>
	<br />
	Return to <a href="index.php">start</a>
{else}
	<h2>Logout failed</h2>
	<br />
	Something went wrong with logging you out of Confusa.
	Seems like you are still authenticated...<br /><br />
	<b>Under normal conditions, you should never see
	this message! Please contact an administrator.</b>
{/if}
