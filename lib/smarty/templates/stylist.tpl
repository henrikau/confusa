{if $person->in_admin_mode() && $person->is_nren_admin()}
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

  {if $edit_help_text}

	[ Texts ]
	[ <a href="?show=css">CSS</a> ]
	[ <a href="?show=logo">Logo</a> ]

	<div class="spacer"></div>
  <fieldset>
  <legend>Change the help-text</legend>
	<form action="" method="POST">
	<table>
		<tr>
		<td>
		<input type="hidden" name="stylist_operation" value="change_help_text" />
		<textarea name="help_text" rows="10" cols="90">{$help_text}</textarea>
		</td>
		</tr>
		<tr>
		<td align="right">
		<input type="submit" name="change" value="Change" />
		</td>
		</tr>
	</table>
	</form>
  </fieldset>
  <div class="spacer"></div>

  <fieldset>
  <legend>Change the "about"-text</legend>
  <form action="" method="POST">
  <table>
	<tr>
	<td>
	<input type="hidden" name="stylist_operation" value="change_about_text" />
	<textarea name="about_text" rows="10" cols="90">{$about_text}</textarea>
	</td>
	</tr>
	<tr>
	<td align="right">
	<input type="submit" name="change" value="Change" />
	</td>
	</tr>
  </table>
  </form>
  </fieldset>

  {elseif $edit_css}
	[ <a href="?show=text">Texts</a> ]
	[ CSS ]
	[ <a href="?show=logo">Logo</a> ]

	<div class="spacer"></div>
	<fieldset>
	<legend>Custom NREN-CSS</legend>
	Edit your NREN's custom-CSS. This will affect how your users will see the page
	once they are logged in. Initially, Confusa's main CSS is shown in this field,
	which you may adapt to fit your needs.
	<div class="spacer"></div>

	<form action="" method="POST">
	<table>
	<tr>
	<td>
		<textarea name="css_content" rows="20" cols="90">{$css_content}</textarea>
	</td>
	</tr>
	<tr>
	<td align="right">
		<input type="hidden" name="stylist_operation" value="change_css" >
		<input type="submit" name="reset" value="Reset"
			onclick="return confirm('Reset CSS to Confusa\'s shipped CSS?')" >
		<input type="submit" name="change" value="Save" >
	</td>
	</tr>
	</table>
	</form>
	</fieldset>

  {elseif $edit_logo}
	[ <a href="?show=text">Texts</a> ]
	[ <a href="?show=css">CSS</a> ]
	[ Logo ]

  {/if}
{/if}
