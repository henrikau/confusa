{if $person->inAdminMode() && $person->isNRENAdmin()}
<h3>NREN customization</h3>
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
[ <a href="?show=mail">Not. Mail</a> ]
<div class="spacer"></div>
  {if isset($edit_help_text) && $edit_help_text === TRUE}
	{include file='stylist/edit_help_text.tpl'}
  {elseif isset($edit_css) && $edit_css === TRUE}
	{include file='stylist/edit_css.tpl'}
  {elseif isset($edit_logo) && $edit_logo === TRUE}
	{include file='stylist/edit_logo.tpl'}
  {elseif isset($edit_mail) && $edit_mail === TRUE}
	{include file='stylist/edit_mail.tpl'}
  {/if}
{/if}
