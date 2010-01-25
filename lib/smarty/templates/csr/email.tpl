{if $email_status == "n" || $email_status == "1"}
<hr style="width: 90%;" />
<div class="spacer"></div>
<p class="info">
  Each certificate can contain zero, one or more of your email-addresses
  depending on how the portal has been configured. We have registred
  that you have {$person->getNumEmails()|escape} registred addresses.
</p>

{if $email_status == "n"}
<p class="info">
  With the current settings, you can choose freely how many of your
  registred addressed you want in the certificate. Note, if you
  uncheck <b>all</b>, no address will be added to the certificate. This
  is most likely <b>not</b> what you want.
</p>
<table style="width: 75%;">
  {* hack for not displaying the initial value. If we do not comment
   * this out, a '-1' will be displayed in the code. *}
  <!--{counter start=-1 skip=1}-->
  {foreach from=$person->getAllEmails() item=addr}
  <tr>
    <td style="width: 30px;"></td>
    <td>
      <input type="checkbox"
	     name="subAltName_email_{counter}"
	     value="{$addr}"
	     checked="checked" />
    </td>
    <td>{$addr}</td>
  </tr>
  {/foreach}
</table>

{elseif $email_status == "1"}
<p class="info">
  With the current settings, you can choose <b>one</b> of your registred
  addressed you want in the certificate. Please select which of the
  available addresses to include in the certificate.
</p>
<table style="width: 75%;">
  <tr>
    <td style="width: 30px;"></td>
    <td>
      {html_radios
      name="subAltName_email_0"
      options=$person->getAllEmails(true)
      selected=0
      separator=" <br /> "}
    </td>
    <td></td>
  </tr>
</table>
{/if}

<div class="spacer"></div>
<hr style="width: 90%;" />
{/if}