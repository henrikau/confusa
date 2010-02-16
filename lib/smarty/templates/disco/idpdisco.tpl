<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>IdPDisco</title>
	<script type="text/javascript" src="../js/jquery-1.4.1.min.js"></script>
	<script type="text/javascript" src="../js/jquery.maphilight.min.js"></script>
	<link rel="stylesheet" href="../css/confusa2.css" type ="text/css" />
</head>
<body>

<div id="site" style="min-height: 500px">

{if isset($error_message)}
	<div style="color: red; font-weight: bold; margin: 1em">{$error_message}</div>
{/if}

<div style="padding: 1em 3em 3em 3em; text-align: center">
<h1 style="margin: 0em 2em 1em 2em; text-align: center">{$l10n_header_choosecountry}</h1>

<div id="mode_selector" style="display: none; text-align: left; margin-bottom: 0.5em">
	<a id="sel_link" href="#">{$l10n_link_textualview}</a>
</div>

<div id="nren_sel_graphical" style="text-align: center">
	<img src="../graphics/map_europe.png" alt="Map of Europe" style="border-style: none" class="map" usemap="#europemap" />
	<map name="europemap">
	<area href="{$disco_path}{$scopedIdPs_se}" alt="Sverige" title="Sverige" shape="poly" coords="384,321, 392,321, 393,309, 407,307, 410,287, 422,287, 423,293, 429,279, 424,279, 422, 287, 410,287, 416,266, 425,252, 417,243, 409,241, 409,215, 427,192, 430,183, 425,180, 427,170, 442,161, 431,131, 412,121, 413,129, 402,129, 402,137, 397,137, 392,146, 395,150, 385,165, 382,187, 385,190, 383,195, 374,198, 374,230, 378,234, 378,238, 375,255, 371,257, 370,270, 367,266, 367,278, 372,278, 370,286, 382,304, 378,309, 382,315" />
	<area href="{$disco_path}{$scopedIdPs_cz}" alt="Česká republika" title="Česká republika" shape="poly" coords="396,416, 387,409, 381,400, 377,394, 380,396, 386,392, 397,385, 397,382, 401,385, 403,385, 405,382, 407,385, 417,388, 416,390, 420,394, 423,392, 422,390, 430,391, 430,394, 438,394, 441,401, 433,411, 426,413, 419,415, 406,412, 404,417" />
	<area href="{$disco_path}{$scopedIdPs_no}" alt="Norge" title="Norge" shape="poly" coords="336,281, 324,273, 323,227, 353,203, 389,133, 413,103, 434,85, 460,87, 456,96, 448,96, 442,100, 440,115, 438,120, 431,119, 425,121, 416,114, 415,118, 412,118, 411,125, 413,129, 401,128, 402,136, 396,136, 392,145, 395,150, 390,163, 382,186, 384,190, 383,195, 376,195, 372,203, 373,221, 373,229, 378,233, 373,240, 372,256, 367,267, 362,257, 358,268, 344,280" />
	<area href="{$disco_path}{$scopedIdPs_fi}" alt="Suomi" title="Suomi" shape="poly" coords="454,247, 484,230, 498,190, 485,182, 486,174, 465,139, 467,126, 455,103, 447,97, 440,101, 438,121, 416,114, 412,120, 427,127, 443,161, 453,171, 433,206, 438,235, 453,245" />
	<area href="{$disco_path}{$scopedIdPs_dk}" alt="Danmark" title="Danmark" shape="poly" coords="374,331, 379,313, 366,315, 365,304, 362,287, 351,298, 343,302, 342,319, 346,320, 346,328, 356,328, 356,320, 365,325" />
	<area href="{$disco_path}{$scopedIdPs_nl}" alt="Nederland" title="Nederland" shape="poly" coords="321,383, 319,380, 307,377, 301,371, 310,362, 310,354, 315,354, 315,362, 320,359, 318,354, 331,349, 329,361, 332,365, 328,371, 321,372" />
	<area href="{$disco_path}{$scopedIdPs_fr}" alt="France" title="France" shape="poly" coords="286,505, 273,505, 260,496, 259,498, 250,497, 250,496, 245,494, 244,492, 238,490, 235,485, 246,454, 247,444, 241,441, 238,435, 239,431, 236,428, 237,424, 231,422, 230,420, 222,416, 219,417, 216,413, 220,411, 219,409, 216,409, 216,405, 233,405, 237,410, 241,410, 242,411, 244,409, 249,410, 247,401, 242,401, 244,402, 239,402, 239,399, 242,401, 247,401, 247,394, 253,395, 254,400, 263,403, 266,401, 266,397, 280,392, 281,382, 290,380, 292,385, 295,385, 296,389, 299,390, 299,391, 304,393, 304,399, 310,396, 310,400, 315,405, 325,407, 330,411, 335,411, 341,414, 334,434, 328,436, 324,442, 322,442, 322,446, 319,449, 318,454, 322,450, 326,451, 325,460, 330,466, 327,468, 324,469, 328,474, 325,478, 328,482, 334,483, 332,488, 348,506, 343,508, 343,521, 348,523, 349,523, 350,523, 354,507, 350,504, 350,501, 343,508, 348,506, 332,488, 322,497, 313,499, 294,490, 286,494" />
	</map>
</div>
<div id="nren_sel_textual">
	<ul style="text-align: left; margin: 1em 0em 0em 3em; list-style-type: none">
		<li><a href="{$disco_path}{$scopedIdPs_cz}">Česká republika</a></li>
		<li><a href="{$disco_path}{$scopedIdPs_dk}">Danmark</a></li>
		<li><a href="{$disco_path}{$scopedIdPs_fr}">France</a></li>
		<li><a href="{$disco_path}{$scopedIdPs_nl}">Nederland</a></li>
		<li><a href="{$disco_path}{$scopedIdPs_no}">Norge</a></li>
		<li><a href="{$disco_path}{$scopedIdPs_fi}">Suomi</a></li>
		<li><a href="{$disco_path}{$scopedIdPs_se}">Sverige</a></li>
	</ul>
</div>

</div>
</div>

<script type="text/javascript">
		var linkTextGraphical = '{$l10n_link_graphicalview}';
		var linkTextTextual = '{$l10n_link_textualview}';

{literal}
		/* do not display the textual selection if the user has JavaScript enabled */
		$('#nren_sel_textual').toggle();
		$('#mode_selector').toggle();

		$("#mode_selector > a").toggle(function() {
			$('#nren_sel_textual').toggle();
			$('#nren_sel_graphical').toggle();
			$(this).html(linkTextGraphical);
		}, function() {
			$('#nren_sel_textual').toggle();
			$('#nren_sel_graphical').toggle();
			$(this).html(linkTextTextual);
		});

		$(function() {
			$('.map').maphilight({stroke: true, strokeColor: '000000', fillColor: '942039', fillOpacity: 0.8, fade: false});
		});
{/literal}

</script>

</body>
</html>
