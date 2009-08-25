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
