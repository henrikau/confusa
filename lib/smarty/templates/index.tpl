
<h1>{$system_title}</h1>
<br />

{if ! $person->isAuth()}
<p class="info">
  {$unauth_welcome_1}<br />
  {$unauth_login_notice}
</p>

<center>
<br />
<hr width="60%"/>
<br />
<h2><a href="?start_login=yes">Log in</a></h2>
<br />
<hr width="60%"/>
<br />
</center>

{else}


	{if $person->getMode() == 0}

	<h3>Showing normal-mode splash</h3>
	{elseif $person->getMode() == 1}

	<h3>Showing admin-mode splash</h3>
	{/if}
{/if}
