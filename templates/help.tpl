<h2>{$l10n_heading_help}</h2>
<div class="spacer"></div>

{if isset($nren_help_text)}
{* ------------------------------
 * NREN specific help
 * ------------------------------*}
<div class="spacer"></div>
<h3>{$nren}{$l10n_heading_nrenadvice}</h3>
{$nren_help_text}

{elseif isset($nren_unset_help_text) && isset($nren_contact_email)}
{* ------------------------------
 * NREN-help not set, post message to user to poke them
 * ------------------------------*}
{$nren_unset_help_text|escape}
<p class="center">
  <a href="mailto:{$nren_contact_email|escape}">{$nren_contact_email|escape}</a>
</p>
<div class="spacer"></div>
{/if}

{* Start of generic help, this will be posted regardless of user is
AuthN or not
*}
<fieldset>
<legend>FAQ</legend>
<div class="spacer"></div>


<a href="#"  class="eh_head" id="faq1">{$index_faq_heading1}</a>
<div class="eh_toggle_container">{$index_faq_text1}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="faq2">{$index_faq_heading2}</a>
<div class="eh_toggle_container">{$index_faq_text2}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="faq3">{$index_faq_heading3}</a>
<div class="eh_toggle_container">{$index_faq_text3}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="faq4">{$index_faq_heading4}</a>
<div class="eh_toggle_container">{$index_faq_text4}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="faq5">{$index_faq_heading5}</a>
<div class="eh_toggle_container">{$index_faq_text5}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="toc_csr_create">{$index_faq_heading6}</a>
<div class="eh_toggle_container">{$index_faq_text6}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="toc_supported_browsers">{$index_faq_heading7}</a>
<div class="eh_toggle_container">{$index_faq_text7}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="toc_export">{$index_faq_heading8}</a>
<div class="eh_toggle_container">{$index_faq_text8}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="toc_pkcs12">{$index_faq_heading9}</a>
<div class="eh_toggle_container">{$index_faq_text9}
    {if isset($portal_escience) and $portal_escience}
        {$index_faq_text9_escience}
    {/if}
</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="toc_export_chrome_linux">{$index_faq_heading10}</a>
<div class="eh_toggle_container">{$index_faq_text10}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="toc_import_chrome">{$index_faq_heading11}</a>
<div class="eh_toggle_container">{$index_faq_text11}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="toc_import_ca">{$index_faq_heading12}</a>
<div class="eh_toggle_container">{$index_faq_text12}</div>
<div class="spacer"></div>

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
	$("#toc_csr_create").toggle(
	function () {literal}{{/literal}
		$(this).html("{$index_faq_heading6}"); {literal}}{/literal},
	function () {literal}{{/literal}
		$(this).html("{$index_faq_heading6}"); {literal}}{/literal}
	);

	$("#toc_supported_browsers").toggle(
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading7}"); {literal}}{/literal},
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading7}"); {literal}}{/literal}
                );


	$("#toc_export").toggle(
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading8}"); {literal}}{/literal},
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading8}"); {literal}}{/literal}
                );

	$("#toc_pkcs12").toggle(
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading9}"); {literal}}{/literal},
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading9}"); {literal}}{/literal}
                );

	$("#toc_export_chrome_linux").toggle(
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading10}"); {literal}}{/literal},
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading10}"); {literal}}{/literal}
                );

	$("#toc_import_chrome").toggle(
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading10}"); {literal}}{/literal},
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading10}"); {literal}}{/literal}
                );

	$("#toc_import_ca").toggle(
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading10}"); {literal}}{/literal},
            function () {literal}{{/literal}
                $(this).html("{$index_faq_heading10}"); {literal}}{/literal}
                );

</script>

</fieldset>
