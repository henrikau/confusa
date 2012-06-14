<fieldset>
<legend>FAQ</legend>
<br />

<a href="#"  class="eh_head" id="faq1">{$index_faq_heading1}</a>
<div class="eh_toggle_container">{$index_faq_text1}</div>
<br />
<a class="eh_head" href="#" id="faq2">{$index_faq_heading2}</a>
<div class="eh_toggle_container">{$index_faq_text2}</div>
<br />
<a class="eh_head" href="#" id="faq3">{$index_faq_heading3}</a>
<div class="eh_toggle_container">{$index_faq_text3}</div>
<br />
<a class="eh_head" href="#" id="faq4">{$index_faq_heading4}</a>
<div class="eh_toggle_container">{$index_faq_text4}</div>
<br />
<a class="eh_head" href="#" id="faq5">{$index_faq_heading5}</a>
<div class="eh_toggle_container">{$index_faq_text5}</div>
<br />

<script type="text/javascript">
	$("#faq1").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading1}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading1}"); {literal}}{/literal}
	);
	$("#faq2").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading2}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading2}"); {literal}}{/literal}
	);
	$("#faq3").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading3}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading3}"); {literal}}{/literal}
	);
	$("#faq4").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading4}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading4}"); {literal}}{/literal}
	);
	$("#faq5").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading5}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading5}"); {literal}}{/literal}
	);
</script>

</fieldset>


<h2>{$l10n_heading_help}</h2>
<div class="spacer"></div>
{*
 *}
{if isset($nren_help_text)}
<div class="spacer"></div>
<h3>{$nren}{$l10n_heading_nrenadvice}</h3>
{$nren_help_text}
{*
 *}
{elseif isset($nren_unset_help_text) && isset($nren_contact_email)}
{$nren_unset_help_text|escape}
<p class="center">
<a href="mailto:{$nren_contact_email|escape}">{$nren_contact_email|escape}</a>
</p>
{*
 *}
{else}
<div class="spacer"></div>
{* Generic, NREN-independent help can be here *}
{$help_file}
{/if}
<br />
