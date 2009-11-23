<fieldset>
	<legend>Custom notification template</legend>
	<p class="info">
	Edit your NREN's notification mail. This is the message that your users will
	receive when a certificate is issued for them by the portal. By default it
	includes information considered useful such as a download-link to the
	certificate.
	</p>
	<p class="info">
	Note: HTML tags will be stripped. {literal}{$varname}{/literal} are variables.
	</p>
	<div style="padding-top: 1em; padding-bottom: 2em">
		<span style="font-style: italic">
		<a href="javascript:void(0)" class="exphead" onclick="toggleExpand(this)"><span class="expchar">+</span>Which variables can I use?</a>
		</span>
		<div class="expcont">
		{if count($tags) > 0}
			<ul style="margin-left: 5%; margin-top: 1em; font-style: italic">
				{foreach from=$tags item=tag}
					<li>{literal}{${/literal}{$tag}{literal}}{/literal}</li>
				{/foreach}
			</ul>
		{/if}
		</div>
	</div>

	<form action="" method="post">
	<div style="width: 90%">
		<textarea style="width: 100%" name="mail_content" rows="20" cols="80">{$mail_content}</textarea>
	</div>
	<div class="spacer"></div>
	<div style="width: 90%">
		<span style="float: left"><input type="submit" name="download" value="Download" /></span>
		<span style="float: right">
			 <input type="submit" name="reset" value="Reset"
			        onclick="return confirm('Reset notification mail template to Confusa\'s shipped template?')" />
			<input type="submit" name="change" value="Update" />
		</span>
		<input type="hidden" name="stylist_operation" value="change_mail" />
	</div>
	</form>
</fieldset>
