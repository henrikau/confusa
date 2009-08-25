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
  {if $edit_help_text}
	{$edit_help_text}  
  {elseif $edit_css}
	{$edit_css}
  {elseif $edit_logo}
	{$edit_logo}
  {/if}
{/if}
