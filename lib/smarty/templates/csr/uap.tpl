<fieldset>
<legend>{$csr_aup_title}</legend>

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

<div>
{if strlen($privacy_notice_text) > 0}
<strong>{$l10n_privacy_notice_header}</strong>:

<span id="shortPrivacyNotice" style="display: none">
{$privacy_notice_text|truncate:50:"":true}
<a href="#" title="Click to read full notice" onclick="togglePrivacyNotice()">...<img src="graphics/triangle_down.png" alt="Expand" /></a>
</span>

<span id="fullPrivacyNotice">
	{$privacy_notice_text} <a href="#" onclick="togglePrivacyNotice();"><img src="graphics/triangle_up.png" alt="Collapse" /> Collapse.</a>
</span>
{/if}
</div>

<div class="spacer"></div>
</fieldset>

{literal}
<script type="text/javascript">

var fullPrivNot = document.getElementById('fullPrivacyNotice');
var shortPrivNot = document.getElementById('shortPrivacyNotice');

fullPrivNot.style.display="none";
shortPrivNot.style.display="block";

function togglePrivacyNotice()
{
	if (fullPrivNot.style.display == "block") {
		fullPrivNot.style.display = "none";
		shortPrivNot.style.display = "block";
	} else {
		fullPrivNot.style.display = "block";
		shortPrivNot.style.display = "none";
	}
}
</script>
{/literal}
