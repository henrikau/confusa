<fieldset>
<legend>{$csr_aup_title}</legend>

<div class="spacer"></div>

<p style="text-align: center; font-style: italic; font-weight: bold">
  <input type="checkbox"
	 name="aup_box"
	 value="user_agreed"
	 {if $aup_box_checked}
	 checked="checked"
	 {/if}
	 />
  {$csr_aup_agreement}
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
