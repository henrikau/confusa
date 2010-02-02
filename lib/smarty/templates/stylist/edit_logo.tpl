{* Beware, here be big fat clot of JavaScript. Started out very small. Really. *}
{literal}
<script type="text/javascript">
	function uploadLogo(pos, infoMessage)
	{
		var logoForm = document.getElementById('logoUpForm');
		var posParam = document.getElementById('posParam');
		var infoDiv = document.getElementById('infoDiv');
		/* this will contain the description of the logo */
		var heading = document.createElement("h4");

		/* show the upload form */
		logoForm.style.display='block';
		infoDiv.style.display='block';
		infoDiv.innerHTML = "<span>" + infoMessage + "<\/span><br />" + infoDiv.innerHTML;

		switch(pos) {
		case 'tl':
			posParam.value="tl";

			var logo=document.getElementById('logo_tl');
			heading.innerHTML = "Left header logo";

			/* show a delete link below the image */
			var deleteLink = document.getElementById('delete_tl');
			/* make all the other logos invisible */
			document.getElementById('logo_tc').style.display='none';
			document.getElementById('logo_bg').style.display='none';
			document.getElementById('logo_tr').style.display='none';
			document.getElementById('logo_bl').style.display='none';
			document.getElementById('logo_bc').style.display='none';
			document.getElementById('logo_br').style.display='none';
			break;
		case 'tc':
			posParam.value="tc";

			var logo=document.getElementById('logo_tc');
			heading.innerHTML = "Central header logo";

			/* show a delete link above the image */
			var deleteLink = document.getElementById('delete_tc');

			document.getElementById('logo_tl').style.display='none';
			document.getElementById('logo_tr').style.display='none';
			document.getElementById('logo_bg').style.display='none';
			document.getElementById('logo_bl').style.display='none';
			document.getElementById('logo_bc').style.display='none';
			document.getElementById('logo_br').style.display='none';
			break;
		case 'tr':
			posParam.value="tr";

			var logo=document.getElementById('logo_tr');
			heading.innerHTML = "Right header logo";

			/* show a delete link above the image */
			var deleteLink = document.getElementById('delete_tr');

			document.getElementById('logo_tl').style.display='none';
			document.getElementById('logo_tc').style.display='none';
			document.getElementById('logo_bg').style.display='none';
			document.getElementById('logo_bl').style.display='none';
			document.getElementById('logo_bc').style.display='none';
			document.getElementById('logo_br').style.display='none';
			break;
		case 'bg':
			posParam.value="bg";

			var logo=document.getElementById('logo_bg');
			heading.innerHTML = "Background image";

			/* show a delete link above the image, iff the image exists */
			var deleteLink = document.getElementById('delete_bg');

			document.getElementById('logo_tl').style.display='none';
			document.getElementById('logo_tc').style.display='none';
			document.getElementById('logo_tr').style.display='none';
			document.getElementById('logo_bl').style.display='none';
			document.getElementById('logo_bc').style.display='none';
			document.getElementById('logo_br').style.display='none';
			break;
		case 'bl':
			posParam.value="bl";

			var logo=document.getElementById('logo_bl');
			heading.innerHTML = "Left footer logo";

			/* show a delete link above the image, iff the image exists */
			var deleteLink = document.getElementById('delete_bl');

			document.getElementById('logo_tl').style.display='none';
			document.getElementById('logo_tc').style.display='none';
			document.getElementById('logo_tr').style.display='none';
			document.getElementById('logo_bg').style.display='none';
			document.getElementById('logo_bc').style.display='none';
			document.getElementById('logo_br').style.display='none';
			break;
		case 'bc':
			posParam.value="bc";

			var logo=document.getElementById('logo_bc');
			heading.innerHTML = "Central footer logo";

			var deleteLink = document.getElementById('delete_bc');

			document.getElementById('logo_tl').style.display='none';
			document.getElementById('logo_tc').style.display='none';
			document.getElementById('logo_tr').style.display='none';
			document.getElementById('logo_bg').style.display='none';
			document.getElementById('logo_bl').style.display='none';
			document.getElementById('logo_br').style.display='none';
			break;
		case 'br':
			posParam.value="br";

			var logo=document.getElementById('logo_br');
			heading.innerHTML = "Right footer logo";

			var deleteLink = document.getElementById('delete_br');

			document.getElementById('logo_tl').style.display='none';
			document.getElementById('logo_tc').style.display='none';
			document.getElementById('logo_tr').style.display='none';
			document.getElementById('logo_bg').style.display='none';
			document.getElementById('logo_bl').style.display='none';
			document.getElementById('logo_bc').style.display='none';
			break;
		}

		if (logo) {
			/* remove the lengthy explanation */
			document.getElementById('explanation').style.display='none';
			/* remove the size restrictions */
			logo.removeAttribute("style");
			logo.style.maxWidth = "100%";
			/* Insert the heading in front of the image */
			logo.insertBefore(heading, deleteLink);


			/* remove the positioning marker above the logo */
			var elementList = logo.getElementsByTagName("p");
			if (elementList.length > 0) {
				/* Insert the heading in front of the image, add width from
				   positioning marker  */
				heading.innerHTML += " (width: " + elementList[0].innerHTML + ")";
				logo.removeChild(elementList[0]);
			}

			var logo_img = logo.getElementsByTagName("img");

			if (logo_img.length > 0) {
				/* show deleteLink if there is an image */
				deleteLink.style.display = 'block';

				/* remove the link surrounding the image */
				var logo_cp = logo_img[0].cloneNode(false);
				logo.insertBefore(logo_cp, deleteLink);
			}

			elementList = logo.getElementsByTagName("a");

			if (elementList.length > 0) {
				logo.removeChild(elementList[0]);
			}
		}
	}

</script>


{/literal}

<div class="tabheader">
<ul class="tabs">
<li><a href="?show=text">{$l10n_tab_texts}</a></li>
<li><a href="?show=css">{$l10n_tab_css}</a></li>
<li><span>{$l10n_tab_logo}</span></li>
<li><a href="?show=title">{$l10n_tab_portaltitle}</a></li>
<li><a href="?show=mail">{$l10n_tab_notificationmail}</a></li>
</ul>
</div>

<fieldset>
  <legend>{$l10n_legend_nrenlogo}</legend>
  <p class="info">
    {$l10n_infotext_nrenlogo1}
  </p>
  <p class="info">
    {$l10n_infotext_nrenlogo2} {$extensions|escape}
  </p>
  <div id="explanation">
  <p class="info">
	<noscript>
		<strong style="color: #ff0000">{$l10n_notification_javascript}</strong>
	</noscript>
	{$l10n_infotext_nrenlogo3}
  </p>
  <p class="info">
	{$l10n_infotext_nrenlogo4}
  </p>
  </div>


  <div class="logo_area" style="margin: 2em 0em 1em 0em">
  <div id="header_line" style="clear: both">
	{***************
	 * left header *
	 ***************}
    <div id="logo_tl" style="float:left; overflow: hidden; width: 90px; max-height: 90px; min-width: 90px; max-width: 90px">
		<p style="border-width: 1px 1px 0px 1px; border-style: solid; margin-bottom: 0.5em; font-size: 0.8em">
			{if isset($css_tl)}
				{$css_tl}
			{/if}
		</p>

		<div id="delete_tl" style="display: none">
		<form action="" method="post">
			<input type="hidden" name="stylist_operation" value="delete_logo" />
			<input type="hidden" name="position" value="tl" />
			<input type="submit" name="delete" value="{$l10n_button_deletelogo}" />
		</form>
		</div>
		<a href="#" onclick="uploadLogo('tl', '{$l10n_js_mlogotl}')">
			{if isset($logo_tl)}
				<img class="logos" src="{$logo_tl}"
				alt="{$l10n_title_editlogotl}" title="{$l10n_title_editlogotl}" />
			{else}
				{$l10n_label_uploadlogotl}
			{/if}
		</a>
	</div>
	{******************
	 * central header *
	 ******************}
	 <div id="logo_tc" style="float:left; width: 250px; overflow: hidden; min-width: 250px; max-width: 250px; min-height: 90px; height: 90px; max-height: 90px; text-align: center">
		<p style="border-width: 1px 1px 0px 1px; border-style: solid; margin-bottom: 0.5em; font-size: 0.8em; text-align: center">
			{if isset($css_tc)}
				{$css_tc}
			{/if}
		</p>

		<div id="delete_tc" style="display: none">
		<form action="" method="post">
			<input type="hidden" name="stylist_operation" value="delete_logo" />
			<input type="hidden" name="position" value="tc" />
			<input type="submit" name="delete" value="{$l10n_button_deletelogo}" />
		</form>
		</div>
		<a class="logos" href="#" onclick="uploadLogo('tc', '{$l10n_js_mlogotc}')">
		{if isset($logo_tc)}

			<img class="logos" src="{$logo_tc}"
			alt="{$l10n_title_editlogotc}" title="{$l10n_title_editlogotc}" />
		{else}
			{$l10n_label_uploadlogotc}
		{/if}
		</a>
	</div>
	{****************
	 * right header *
	 ****************}
	<div id="logo_tr" style="float:left; overflow: hidden; max-height: 90px; width: 90px; min-width: 90px; max-width: 90px; min-height: 80px; text-align: right">
		<p style="border-width: 1px 1px 0px 1px; border-style: solid; margin-bottom: 0.5em; font-size: 0.8em">
			{if isset($css_tr)}
				{$css_tr}
			{/if}
		</p>
		<div id="delete_tr" style="display: none">
		<form action="" method="post">
			<input type="hidden" name="stylist_operation" value="delete_logo" />
			<input type="hidden" name="position" value="tr" />
			<input type="submit" name="delete" value="{$l10n_button_deletelogo}" />
		</form>
		</div>
		<a href="#" onclick="uploadLogo('tr', '{$l10n_js_mlogotr}')">
		{if isset($logo_tr)}
			<img class="logos" src="{$logo_tr}"
			alt="{$l10n_title_editlogotr}" title="{$l10n_title_editlogotr}" />
		{else}
			{$l10n_label_uploadlogotr}
		{/if}
		</a>
	</div>
	</div>
	{**************
	 * background *
	 **************}
	<div id="logo_bg" style="clear: both; overflow: hidden; float:left; width: 430px; max-width: 430px; max-height: 430px; min-width: 430px; margin: 1em 0px 1em 0px; text-align: center">
	<p style="border-width: 1px 1px 0px 1px; border-style: solid; margin-bottom: 0.5em; font-size: 0.8em">
			900 px
		</p>

		<a href="#" onclick="uploadLogo('bg', '{$l10n_js_mlogobg}')">
		{if isset($logo_bg)}
			<img class="logos" src="{$logo_bg}"
			alt="{$l10n_title_changelogobg}" title="{$l10n_title_changelogobg}" />
		{else}
			{$l10n_label_uploadlogobg}
		{/if}
		</a>
		<div id="delete_bg" style="display: none; margin-top: 0.5em">
		<form action="" method="post">
			<input type="hidden" name="stylist_operation" value="delete_logo" />
			<input type="hidden" name="position" value="bg" />
			<input type="submit" name="delete" value="{$l10n_button_deletelogo}" />
		</form>
		</div>
	</div>
	<div id="footer_line" style="clear: both">
	{***************
	 * left footer *
	 ***************}
	<div id="logo_bl" style="float:left; overflow: hidden; width: 90px; max-height: 90px; min-width: 90px; max-width: 90px">
	 <p style="border-width: 1px 1px 0px 1px; border-style: solid; margin-bottom: 0.5em; font-size: 0.8em">
			{if isset($css_bl)}
				{$css_bl}
			{/if}
		</p>

		<div id="delete_bl" style="display: none">
		<form action="" method="post">
			<input type="hidden" name="stylist_operation" value="delete_logo" />
			<input type="hidden" name="position" value="bl" />
			<input type="submit" name="delete" value="{$l10n_button_deletelogo}" />
		</form>
		</div>
		<a href="#" onclick="uploadLogo('bl', '{$l10n_js_mlogobl}')">
		{if isset($logo_bl)}
			<img class="logos" src="{$logo_bl}" alt="{$l10n_title_editlogobl}"
			     title="{$l10n_title_editlogobl}" />
		{else}
			{$l10n_label_uploadlogobl}
		{/if}
		</a>
	</div>
	{******************
	 * central footer *
	 ******************}
	<div id="logo_bc" style="float:left; overflow: hidden; width: 250px; min-width: 250px; max-height: 90px; max-width: 250px; text-align: center">
	<p style="border-width: 1px 1px 0px 1px; border-style: solid; margin-bottom: 0.5em; font-size: 0.8em">
			{if isset($css_bc)}
				{$css_bc}
			{/if}
		</p>
		<div id="delete_bc" style="display: none">
		<form action="" method="post">
			<input type="hidden" name="stylist_operation" value="delete_logo" />
			<input type="hidden" name="position" value="bc" />
			<input type="submit" name="delete" value="{$l10n_button_deletelogo}" />
		</form>
		</div>
		<a href="#" onclick="uploadLogo('bc', '{$l10n_js_mlogobc}')">
		{if isset($logo_bc)}
			<img class="logos" src="{$logo_bc}" alt="{$l10n_title_editlogobc}"
			     title="{$l10n_title_editlogobc}" />
		{else}
			{$l10n_label_uploadlogobc}
		{/if}
		</a>
	</div>
	{****************
	 * right footer *
	 ****************}
	<div id="logo_br" style="float:left; overflow: hidden; width: 90px; min-width: 90px; max-width: 90px; text-align: right">
		<p style="border-width: 1px 1px 0px 1px; border-style: solid; margin-bottom: 0.5em; font-size: 0.8em">
			{if isset($css_br)}
				{$css_br}
			{/if}
		</p>
		<div id="delete_br" style="display: none">
		<form action="" method="post">
			<input type="hidden" name="stylist_operation" value="delete_logo" />
			<input type="hidden" name="position" value="br" />
			<input type="submit" name="delete" value="{$l10n_button_deletelogo}" />
		</form>
		</div>
		<a href="#" onclick="uploadLogo('br', '{$l10n_js_mlogobr}')">
		{if isset($logo_br)}
			<img class="logos" src="{$logo_br}"
			     alt="{$l10n_title_editlogobr}" title="{$l10n_title_editlogobr}" />
		{else}
			{$l10n_label_uploadlogobr}
		{/if}
		</a>
	</div>
	</div>
  </div>

  <form id="logoUpForm" style="display: none" action="" method="post"
        enctype="multipart/form-data">
	<div>
		<input type="hidden" name="stylist_operation" value="upload_logo" />
		<input id="posParam" type="hidden" name="position" value="" />
		<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
		<input type="file" name="nren_logo" />
		<input type="submit" value="{$l10n_button_uploadimg}" />
	</div>
  </form>
  <div class="spacer"></div>
  <div id="infoDiv" style="margin-bottom: 1em; display: none">
  <a href="stylist.php?show=logo" style="margin-bottom: 1em">{$l10n_link_goback}</a>
  </div>
</fieldset>
