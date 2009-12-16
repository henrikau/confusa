
<br />
<br />
{if ! $person->isAuth()}
<p class="info">
  {$unauth_welcome_1}<br />
  {$unauth_login_notice}
</p>

<br />
<hr style="width: 90%"/>
<br />
<center>
<h2><a href="?start_login=yes">Log in</a></h2>
</center>
<br />
<hr style="width: 90%"/>
<br />

{else}
<p class="info">
{$auth_welcome_1}
</p>
<br />
<hr style="width 90%" />
<br />
<h3>Info about you</h3>
<p class="info">
  This is information we have received from your home organization
  combined with information entered for your NREN ({$nren->getName()|escape})
  and subscriber ({$subscriber->getOrgName()|escape}).
</p>
<br />
{assign var='bg1' value='style="background-color: #ededed"'}
{assign var='bg2' value='style="background-color: #ffffff"'}

<table class="small" style="width: 90%; table-layout: fixed">
   <tr {$bg1}>
     <td style="width: 20%"><b>Name:</b></td>
     <td style="width: 20px"></td>
     <td>{$person->getName()|escape}</td>
   </tr>

   <tr {$bg2}>
     <td><b>E-mail address:</b></td>
     <td></td>
     <td>{$person->getEmail()|escape}</td>
   </tr>

   <tr {$bg1}>
     <td><b>Entitlement:</b></td>
     <td></td>
     <td>{$person->getEntitlement()|escape}</td>
   </tr>

   <tr {$bg2}>
     <td><b>Unique Name:</b></td>
     <td></td>
     <td>{$person->getEPPN()|escape}</td>
   </tr>

   <tr {$bg1}>
     <td><b>Home organization:</b></td>
     <td></td>
     <td>
       {if $subscriber}
       {$subscriber->getOrgName()|escape}
       {/if}
     </td>
   </tr>

   <tr {$bg2}>
     <td><b>Country:</b></td>
     <td></td>
     <td>{$nren->getCountry()|escape}</td>
   </tr>

   <tr {$bg1}>
     <td><b>Org ID:</b></td>
     <td></td>
     <td>
       {if $subscriber}
       {$subscriber->getIdPName()|escape}
       {/if}
     </td>
   </tr>

   <tr {$bg2}>
     <td><b>NREN-name:</b></td>
     <td></td>
     <td>{$person->getNREN()|escape}</td>
   </tr>

   <tr {$bg1}>
     <td><b>Full \DN</b></td>
     <td></td>
     <td><pre class="certificate">{$person->getX509SubjectDN()|escape}</pre></td>
   </tr>

 </table>
 <br />

<br />
<hr style="width: 90%" />
<br />
{/if}

<br />
<fieldset>
<legend>FAQ</legend>
<br />

<h4><a href="javascript:void(0)"
       class="exphead"
       onclick="toggleExpand(this)">
    <span class="expchar">+</span>
    {$index_faq_heading1}
  </a>
</h4>
<div class="expcont">
  <p>{$index_faq_text1}</p>
</div>
<br />

<h4><a href="javascript:void(0)"
       class="exphead"
       onclick="toggleExpand(this)">
    <span class="expchar">+</span>
    {$index_faq_heading2}
  </a>
</h4>
<div class="expcont">
  <p>{$index_faq_text2}</p>
</div>

<br />

<h4>
  <a href="javascript:void(0)"
     class="exphead"
     onclick="toggleExpand(this)">
    <span class="expchar">+</span>
    {$index_faq_heading3}
  </a>
</h4>
<div class="expcont">
  <p>{$index_faq_text3}</p>
</div>
<br />

<h4><a href="javascript:void(0)"
       class="exphead"
       onclick="toggleExpand(this)">
    <span class="expchar">+</span>
    {$index_faq_heading4}
  </a>
</h4>
<div class="expcont">
  <p>{$index_faq_text4}</p>
</div>
<br />

<h4>
  <a href="javascript:void(0)"
     class="exphead"
     onclick="toggleExpand(this)">
    <span class="expchar">+</span>
    {$index_faq_heading5}</a>
</h4>
<div class="expcont">
  <p>{$index_faq_text5}</p>
</div>
<br />
</fieldset>
