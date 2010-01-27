<form id="startForm" method="post" action="process_csr.php">
<div class="spacer"></div>
{include file="csr/uap.tpl"}
<div class="spacer"></div>
<fieldset>
<legend>Apply for a certificate in browser</legend>

<div class="spacer"></div>
<div id="info_view">
	<p class="info">Press the start button <b>once</b> to generate a certificate request in your browser.<br /><br />
	Sometimes it will take a little while until you can see a browser reaction and there
	can be delays between browser actions.</p>
</div>
<div class="spacer"></div>
{include file="csr/email.tpl"}
<div class="spacer"></div>
	  <p>
	    <input type="hidden"
		   name="browserSigning"
		   value="start" />
	    {if $user_cert_enabled}
	    <input type="submit"
		   name="Send"
		   id="startButton"
		   value="Apply"
		   onclick="return isBoxChecked(aup_box);" />
	    {* Disable the element if the user does not have the right entitlement *}
	    {else}
	    <input disabled
		   type="submit"
		   name="Send"
		   id="startButton"
		   value="Apply" />
	    {/if}
	</p>
	<br />
</fieldset>
</form>
