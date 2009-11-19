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
	<div style="width: 90%">
		<textarea style="width: 100%" name="css_content" rows="20" cols="80">{$css_content}</textarea>
	</div>
	<div class="spacer"></div>
	<div style="width: 90%">
		<span style="float: left"><input type="submit" name="download" value="Download" /></span>
		<span style="float: right">
			 <input type="submit" name="reset" value="Reset"
			        onclick="return confirm('Reset CSS to Confusa\'s shipped CSS?')" />
			<input type="submit" name="change" value="Update" />
		</span>
		<input type="hidden" name="stylist_operation" value="change_css" />
	</div>
  </form>
</fieldset>
