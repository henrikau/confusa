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
  {if $edit_help_text === TRUE}
	{include file='stylist/edit_help_text.tpl'}
  {elseif $edit_css === TRUE}
	{include file='stylist/edit_css.tpl'}
  {elseif $edit_logo === TRUE}
	{include file='stylist/edit_logo.tpl'}
  {elseif $edit_mail === TRUE}
	{include file='stylist/edit_mail.tpl'}
  {/if}
{/if}
