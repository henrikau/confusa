<fieldset>
  <legend>Custom NREN logo</legend>
  <p class="info">
    Upload a custom logo here. This logo will be displayed on your users' landing
    page, i.e. when the user has logged in to Confusa.
  </p>
  <p class="info">
    Note: Please provide an image with a size of maximally <b>{$width|escape} x {$height|escape}</b>
    pixel!
  </p>
  <p class="info">
    Note: Supported file-extensions are {$extensions|escape}
  </p>
  {if is_null($logo) === FALSE}
  <p><i>Your current logo:</i></p>
  <div class="spacer"></div>
  <img src="{$logo}" alt="Currently uploaded NREN logo" />
  <div class="spacer"></div>
  {/if}
  <form action="" method="post" enctype="multipart/form-data">
	<div>
		<input type="hidden" name="stylist_operation" value="upload_logo" />
		<input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
		<input type="file" name="nren_logo" />
		<input type="submit" value="Upload image" />
	</div>
  </form>

  <div class="spacer"></div>
</fieldset>
