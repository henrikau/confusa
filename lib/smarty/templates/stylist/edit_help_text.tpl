  <fieldset>
  <legend>Change the help-text</legend>
	<p class="info">
	This text will be shown when your users open Confusa's help page, possibly
	together with a generic help message shown by Confusa. If there is anything
	specific that users within the domain of your NREN should know, define it
	here.
	</p>
	<p class="info">
	Note: The field accepts UTF-8 characters. A subset of
	<a href="http://www.textism.com/tools/textile/">Textile</a> syntax is
	supported. No external images, no HTML, sorry.
	</p>
	<form action="" method="post">

	<div style="width: 90%">
		<input type="hidden" name="stylist_operation" value="change_help_text" />
		<textarea style="width: 100%" name="help_text" rows="10" cols="80">{$help_text}</textarea>
	</div>
	<div class="spacer"></div>
	<div style="width: 90%; text-align: right">
		<input type="submit" name="change" value="Change" />
	</div>
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
  <p class="info">
	Note: The field accepts UTF-8 characters. A subset of
	<a href="http://www.textism.com/tools/textile/">Textile</a> syntax is
	supported. No external images, no HTML, sorry.
  </p>
  <form action="" method="post">
	<div style="width: 90%">
		<input type="hidden" name="stylist_operation" value="change_about_text" />
		<textarea name="about_text" style="width: 100%" rows="10" cols="80">{$about_text}</textarea>
	</div>
	<div class="spacer"></div>
	<div style="width: 90%; text-align: right">
		<input type="submit" name="change" value="Change" />
	</div>
  </form>
  </fieldset>
