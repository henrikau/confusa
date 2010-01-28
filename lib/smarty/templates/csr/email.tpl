{if $email_status == "n" || $email_status == "m"}
<hr style="width: 90%;" />
<div class="spacer"></div>
<p class="info">
  {$l10n_infotext_email1} {$person->getNumEmails()|escape} {$l10n_infotext_email2}
</p>
{if $email_status == "n" || $email_status == "m"}
<p class="info">
  {$l10n_infotext_email3}
</p>
<table style="width: 75%;">
  {* we could use html_checkboxes, but getting all the boxes ticked
   required more code than writing the loop manually *}
  {foreach from=$person->getAllEmails() item=addr}
  <tr>
    <td style="width: 30px;"></td>
    <td>
      <input type="checkbox"
	     name="subjAltName_email[]"
	     value="{$addr}"
	     checked="checked"/>
    </td>
    <td>{$addr}</td>
  </tr>
  {/foreach}

</table>

{elseif $email_status == "1"}
<p class="info">
  {$l10n_infotext_email4}
</p>
<table style="width: 75%;">
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

<div class="spacer"></div>
<hr style="width: 90%;" />
{/if}
