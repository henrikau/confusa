<div class="info">
{$maint_header|escape}
</div>
<br />
<br />
<p class="info">
{$maint_msg|escape}
</p>
{if $mode_toggle}

<p class="info">
  {$mode_toggle_text|escape}
</p>

<form method="post" action="portal_config.php">
  {$panticsrf}
  <input type="hidden" name="nren_maint_mode" value="n" />
  <input type="submit" value="{$mode_toggle_button|escape}" />
</form>
{/if}
</div>
</body>
