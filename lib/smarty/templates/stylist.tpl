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

  {if $edit_help_text}

	[ Texts ]
	[ <a href="?show=css">CSS</a> ]
	[ <a href="?show=logo">Logo</a> ]

	<div class="spacer"></div>
  <fieldset>
  <legend>Change the help-text</legend>
	<p class="info">
	This text will be shown when your users open Confusa's help page, possibly
	together with a generic help message shown by Confusa. If there is anything
	specific that users within the domain of your NREN should know, define it
	here.
	</p>
	<br />
	<p class="info">
	Note: Currently, the field accepts UTF-8 characters. No HTML or other markup
	is supported.
	</p>
	<br />
	<form action="" method="post">
	<table>
		<tr>
		<td>
		<input type="hidden" name="stylist_operation" value="change_help_text" />
		<textarea name="help_text" rows="10" cols="80">{$help_text}</textarea>
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
  <p class="info">
  This text will be shown, along with your logo, when a user, who comes from
  an institution that belongs to your NREN, clicks the 'About'-link. Time to
  present yourself!
  </p>
  <br />
  <p class="info">
	Note: Currently, the field accepts UTF-8 characters. No HTML or other markup
	is supported.
  </p>
  <br />
  <form action="" method="post">
  <table>
	<tr>
	<td>
	<input type="hidden" name="stylist_operation" value="change_about_text" />
	<textarea name="about_text" rows="10" cols="80">{$about_text}</textarea>
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
	<p class="info">
	Edit your NREN's custom-CSS. This will affect how your users will see the page
	once they are logged in. Initially, Confusa's main CSS is shown in this field,
	which you may adapt to fit your needs.
	</p>
	<br />
	<p class="info">
	Note: All CSS properties are supported, as long as they don't include ()-brackets.
	This also means that url() is not supported.
	</p>
	<br />
	<form action="" method="post">
	<table>
	<tr>
	<td>
		<textarea name="css_content" rows="20" cols="80">{$css_content}</textarea>
	</td>
	</tr>
	<tr>
	<td align="right">
		<input type="hidden" name="stylist_operation" value="change_css" />
		<input type="submit" name="reset" value="Reset"
			onclick="return confirm('Reset CSS to Confusa\'s shipped CSS?')" />
		<input type="submit" name="change" value="Save" />
	</td>
	</tr>
	</table>
	</form>
	</fieldset>

  {elseif $edit_logo}
	[ <a href="?show=text">Texts</a> ]
	[ <a href="?show=css">CSS</a> ]
	[ Logo ]
	<div class="spacer"></div>
	<fieldset>
	<legend>Custom NREN logo</legend>
	<p class="info">
	Upload a custom logo here. This logo will be displayed on your users' landing
	page, i.e. when the user has logged in to Confusa.
	</p>
	<br />
	<p class="info">
	Note: Please provide an image with a size of maximally <b>{$width} x {$height}</b>
	pixel!
	</p>
	<br />
	<p class="info">
	Note: Supported file-extensions are {$extensions}
	</p>
	<br />
	{if is_null($logo) === FALSE}
		<p><i>Your current logo:</i></p>
		<div class="spacer"></div>
		<img src="{$logo}" alt="Currently uploaded NREN logo" />
		<div class="spacer"></div>
	{/if}
	<form action="" method="post" enctype="multipart/form-data">
	<table>
	<tr>
	<td>
		<input type="hidden" name="stylist_operation" value="upload_logo" />
		<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
		<input type="file" name="nren_logo" />

	</td>
	<td>
		<input type="submit" value="Upload image" />
	</td>
	</tr>
	</table>
	</form>

	<div class="spacer"></div>
	</fieldset>
  {/if}
{/if}
