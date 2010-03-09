{if $person->isNRENAdmin()}
<h3>{$l10n_heading_stylist}</h3>

{ * ------------------------------------------------------------------ *
  * Page header link section
  *
  * ------------------------------------------------------------------ * }

{ *--------------------------------------------------------------------*
  *
  * Customize help texts
  *
  * -------------------------------------------------------------------* }

  {if isset($edit_help_text) && $edit_help_text === TRUE}
	{include file='stylist/edit_help_text.tpl'}
  {elseif isset($edit_css) && $edit_css === TRUE}
	{include file='stylist/edit_css.tpl'}
  {elseif isset($edit_logo) && $edit_logo === TRUE}
	{include file='stylist/edit_logo.tpl'}
  {elseif isset($edit_mail) && $edit_mail === TRUE}
	{include file='stylist/edit_mail.tpl'}
  {elseif isset($edit_title) && $edit_title === TRUE}
	{include file='stylist/edit_title.tpl'}
  {/if}
{/if}
