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
