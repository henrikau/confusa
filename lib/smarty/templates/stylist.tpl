{if $person->inAdminMode() && $person->isNRENAdmin()}
<h3>NREN page customization</h3>
<br />

{ * ------------------------------------------------------------------ *
  * Page header link section
  *
  * ------------------------------------------------------------------ * }

{ *--------------------------------------------------------------------*
  *
  * Customize help texts
  *
  * -------------------------------------------------------------------* }

[ <a href="?show=text">Texts</a> ]
[ <a href="?show=css">CSS</a> ]
[ <a href="?show=logo">Logo</a> ]
[ <a href="?show=map">Map</a> ]
<div class="spacer"></div>
  {if $edit_help_text === TRUE}
	{include file='stylist/edit_help_text.tpl'}
  {elseif $edit_css === TRUE}
	{include file='stylist/edit_css.tpl'}
  {elseif $edit_logo === TRUE}
	{include file='stylist/edit_logo.tpl'}
  {elseif $handle_map === TRUE}
	{include file='stylist/handle_map.tpl'}
  {/if}
{/if}
