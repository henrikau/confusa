<fieldset>
<legend>{$csr_aup_title}</legend>
<div class="spacer"></div>
<h4><a href="javascript:void(0)"
       class="exphead"
       onclick="toggleExpand(this)">
    <span class="expchar">+</span>
    {$l10n_privacy_notice_header}
  </a>
</h4>
<div class="expcont">
  <p class="info">
    {$privacy_notice_text}
  </p>
</div>
<div class="spacer"></div>

<p style="text-align: center; font-style: italic; font-weight: bold">
  <input type="checkbox"
	 name="aup_box"
	 id="aup_box"
	 value="user_agreed"
	 {if $aup_box_checked}
	 checked="checked"
	 {/if}
	 />
  <label for="aup_box">{$csr_aup_agreement}</label>
</p>

<h4><a href="javascript:void(0)"
       class="exphead"
       onclick="toggleExpand(this)">
    <span class="expchar">+</span>
    {$csr_aup_info_short}
  </a>
</h4>
<div class="expcont">
  <p>{$csr_aup_info_long}</p>
</div>
<div class="spacer"></div>
</fieldset>
