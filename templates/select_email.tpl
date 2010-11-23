<h3>{$l10n_heading_step2email}</h3>
<form id="nextForm" action="receive_csr.php" method="post">
<fieldset>
{if $email_status != "0"}
<div class="spacer"></div>
<div>
<p class="info">
  {$l10n_infotext_email1} {$person->getNumEmails()|escape} {$l10n_infotext_email2}
</p>
{if $email_status == "n" || $email_status == "m"}
<p class="info">
  {$l10n_infotext_email3}
</p>
</div>
<table style="width: 75%;"
       summary="{$l10n_pcsr_email_table_summary}">
  {* we could use html_checkboxes, but getting all the boxes ticked
   required more code than writing the loop manually *}

  {* initialize the count *}
  {foreach from=$person->getAllEmails() item=addr}
  <tr>
    <td style="width: 30px;"></td>
    <td>
      <input type="checkbox"
	     name="subjAltName_email[]"
		 id="SAN_{$addr|replace:"@":"_at_"}"
	     value="{$addr}"
	     checked="checked"/>
    </td>
    <td><label for="SAN_{$addr|replace:"@":"_at_"}">{$addr}</label></td>
  </tr>
  {/foreach}

</table>

{elseif $email_status == "1"}
<p class="info">
  {$l10n_infotext_email4}
</p>
<table style="width: 75%;"
       summary="{$l10n_pcsr_email_table_summary}">
  <tr>
    <td style="width: 30px;"></td>
    <td>
      {html_radios
      name="subjAltName_email[]"
      values=$person->getAllEmails()
      output=$person->getAllEmails(true)
      selected=$person->getEmail(0)
      separator=" <br /> "}
    </td>
    <td></td>
  </tr>
</table>
{/if}
{/if}

<div class="spacer"></div>
</fieldset>

<div style="float: right;" class="nav">
		{$panticsrf}
		<input id="nextButton" type="submit" title="{$l10n_button_next}" value="{$l10n_button_next} >" />
</div>
</form>

<div style="float: right;" class="nav">
<form action="confirm_aup.php?{$ganticsrf}" method="get">
	<input id="backButton" type="submit" title="{$l10n_button_back}" value="< {$l10n_button_back}" />
</form>
</div>
