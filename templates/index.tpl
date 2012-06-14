
<br />
<br />
{if ! $person->isAuth()}
<p class="info">
  {$unauth_welcome_1|escape}<br />
  {$unauth_login_notice|escape}
</p>

<div style="text-align: center; margin: 2em 0 2em 0">
<a href="?start_login=yes&amp;{$ganticsrf}">
<img src="graphics/login_button.gif" alt="Login" style="border: 0" />
</a>
</div>

{else} {* person is authenticated, but is the subscriber added? *}

{if is_null($subscriber)} {* no *}
<p class="info">
  You have authenticated, but no subscriber is set. This normally
  indicates that your home-organization is not properly configured at
  the portal.
</p>

<p class="info">
  Most likely, this will happen soon, but in case it does not, please
  contact your local IT-department and ask them about the progress.
</p>

{else} {* yes, subscriber is added, person is authN, show 'about-you'
	  stuff *}
<p class="info">
{$auth_welcome_1|escape}
</p>

<p class="important">
  {$auth_warning_1}
</p>
<br />
<hr style="width 90%" />
<br />

<h3>Info about you</h3>
<p class="info">
  {$attribute_info1|escape}
  ({$nren->getName()|escape})
  {$attribute_info2|escape} ({$subscriber->getOrgName()|escape}).
</p>
<br />
{assign var='bg1' value='style="background-color: #ededed"'}
{assign var='bg2' value='style="background-color: #ffffff"'}

<table class="small" style="width: 90%; table-layout: fixed">
   <tr {$bg1}>
     <td style="width: 20%"><b>{$attribute_name|escape}</b></td>
     <td style="width: 20px"></td>
     <td>{$person->getName()|escape}</td>
   </tr>

   <tr {$bg2}>
     <td><b>{$attribute_email|escape}</b></td>
     <td></td>
     <td>
       <ul style="list-style-image: url(graphics/email.png)" >
        {foreach from=$person->getAllEmails() item=addr}
        <li><a href="mailto:{$addr}">{$addr}</a></li>
       {/foreach}
       </ul>
     </td>

   </tr>

   <tr {$bg1}>
     <td><b>{$attribute_entitlement|escape}</b></td>
     <td></td>
     <td>{$person->getEntitlement()|escape}</td>
   </tr>

   <tr {$bg2}>
     <td><b>{$attribute_eppn|escape}</b></td>
     <td></td>
     <td>{$person->getEPPN()|escape}</td>
   </tr>

   <tr {$bg1}>
     <td><b>{$attribute_orgname|escape}</b></td>
     <td></td>
     <td>
       {if $subscriber}
       {$subscriber->getOrgName()|escape}
       {/if}
     </td>
   </tr>

   <tr {$bg2}>
     <td><b>{$attribute_country|escape}</b></td>
     <td></td>
     <td>{$nren->getCountry()|escape}</td>
   </tr>

   <tr {$bg1}>
     <td><b>{$attribute_idpname|escape}</b></td>
     <td></td>
     <td>
       {if $subscriber}
       {$subscriber->getIdPName()|escape}
       {/if}
     </td>
   </tr>

   <tr {$bg2}>
     <td><b>{$attribute_nrenname|escape}</b></td>
     <td></td>
     <td>{$person->getNREN()|escape}</td>
   </tr>

   <tr {$bg1}>
     <td><b>{$attribute_fulldn|escape}</b></td>
     <td></td>
     <td><pre class="certificate">{$subjectDN|escape}</pre></td>
   </tr>

 </table>
 <br />

<br />
<hr style="width: 90%" />
<br />

{/if} {* else, !is_null($subscriber) *}
{/if} {* !person->isAuth() *}
