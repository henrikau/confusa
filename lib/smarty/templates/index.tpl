
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
