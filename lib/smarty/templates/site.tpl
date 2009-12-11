<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>{$title}</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	{* force off quirks mode in IE8, which will automatically be activated if anything appears above the doctype
	    (like PHP debugging output) *}
	<meta http-equiv="X-UA-Compatible" content="IE=8"/>
	<link rel="shortcut icon" href="graphics/icon.gif" type="image/gif" />
	<link rel="stylesheet" href="css/confusa2.css" type="text/css" />
	<script type="text/javascript" src="js/confusa.js"></script>

	{if isset($extraScripts)}
		{* include additional JavaScript if necessary *}
		{foreach from=$extraScripts item=extraScript}
			<script type="text/javascript" src="{$extraScript}"></script>
		{/foreach}
	{/if}

	{if !is_null($css)}
		<link rel="stylesheet" href="{$css}" type ="text/css" />
	{/if}

	{$extraHeader}

	{literal}
	 <script type="text/javascript">
		//<![CDATA[
		document.write('<style type="text/css">.expcont{display:none}<\/style>');
		//]]>
	</script>
	{/literal}
</head>

<body>
<div id="site">
  <div class="confusa_corners_t">
    <div class="confusa_corners_l">
      <div class="confusa_corners_r">
	<div class="confusa_corners_b">
	  <div class="confusa_corners_tl">
	    <div class="confusa_corners_tr">
	      <div class="confusa_corners_bl">
		<div class="confusa_corners_br">
		  <div class="confusa_corners">
		      <div id="title" style="vertical-align: middle;">
			<a href="index.php">
			  {if is_null($logo)}
			  <img src="graphics/logo-sigma.png"
			       alt="UNINETT Sigma Logo"
			       class="url"/>
			  {else}
			  <img src="{$logo}"
			       alt="NREN logo"
			       class="url"/>
			  {/if}
			  {$system_title}
			</a>
		      </div> <!-- title -->
		    <div id="language_bar">
		    {foreach from=$available_languages key=code item=lang}
			{if $code == $selected_language}
				{$lang} |
			{else}
				<a href="?lang={$code}">{$lang}</a> |
			{/if}
		    {/foreach}
		    </div>
		    <div id="menu">
		      {$menu}
		    </div> <!-- menu -->
		    <div id="content">
		    {if $maint}
		    {$maint}
		    {else}
		      {foreach from=$errors item=error}
		      <div class="message_container error">
			<div class="message_icon">
			<img src="graphics/exclamation.png" alt="" />
			</div>
			<div class="message_body">{$error}</div>
			<div class="clear"></div>
			</div>
		      {/foreach}
		      {foreach from=$successes item=success}
		      <div class="message_container success">
			<div class="message_icon">
			<img src="graphics/accept.png" alt="Information: " />
			</div>
			{$success}
			<div class="clear"></div>
		      </div>
		      {/foreach}
		      {foreach from=$warnings item=warning}
		      <div class="message_container warning">
			<div class="message_icon">
				<img src="graphics/warning.png" alt="Warning: " />
			</div>
			{$warning}
			<div class="clear"></div>
		      </div>
		      {/foreach}
		      {foreach from=$messages item=msg}
		      <div class="message_container message">
			<div class="message_icon">
			<img src="graphics/information.png" alt="Information: " />
			</div>
			{$msg}
			<div class="clear"></div>
		      </div>
		      {/foreach}
		      {$content}
		    {/if} {* maint *}
		    </div> <!-- content -->
		  </div> <!-- rounded borders -->
		</div>
	      </div>
	    </div>
	  </div>
	</div>
      </div>
    </div>
  </div> <!-- end rounded border -->

</div> <!-- site -->
{if $db_debug}
<div style="text-align: center;">
{$db_debug}
</div>
{/if}
</body>
</html>
