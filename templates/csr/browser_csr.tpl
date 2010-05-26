<form id="startForm" method="post" action="process_csr.php">
<div class="spacer"></div>
{include file="csr/uap.tpl"}
<div class="spacer"></div>
<fieldset>
<legend>{$l10n_legend_browsercsr}</legend>
<div class="spacer"></div>
<div id="info_view">
	<p class="info">
		{$l10n_infotext_browsercsr1}
	</p>
</div>
<div class="spacer"></div>
{include file="csr/email.tpl"}
<div class="spacer"></div>
	  <p>
	    {$panticsrf}
	    <input type="hidden"
		   name="browserSigning"
		   value="start" />
	    {if $user_cert_enabled}
	    <input type="submit"
		   name="Send"
		   id="startButton"
		   value="{$l10n_button_applybrowsercsr}"
		   onclick="return isBoxChecked(aup_box);" />
	    {* Disable the element if the user does not have the right entitlement *}
	    {else}
	    <input disabled
		   type="submit"
		   name="Send"
		   id="startButton"
		   value="{$l10n_button_applybrowsercsr}" />
	    {/if}
	</p>
	<br />
</fieldset>
</form>
