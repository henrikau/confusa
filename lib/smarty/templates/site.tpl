<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>{$title}</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<link rel="shortcut icon" href="graphics/icon.gif" type="image/gif" />
	<link rel="stylesheet" href="confusa2.css" type="text/css" />
	{$extraHeader}
</head>

<body>
	<div id="logo"><img src="graphics/logo-sigma.png" alt="" /></div>
	<div id="title">Confusa</div>
	<div id="site">
	<div id="menu">
		{$menu}
	</div>
	
	<div id="content">
		{foreach from=$errors item=error}
		<div class="error">{$error}</div>
		{/foreach}
		{$content}
	</div>
	</div>
</body>
</html>