<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>IdPDisco</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<script type="text/javascript" src="../js/jquery-1.6.min.js"></script>
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

<div id="nren_sel_graphical" style="text-align: center; display: none">
	<img src="../graphics/map_europe.png" alt="Map of Europe" style="border-style: none" class="map" usemap="#europemap" />
	<map name="europemap">

	{if isset($scopedIdPs_se)}
		<area href="javascript:$('#country_se').submit();" alt="Sverige" title="Sverige" shape="poly" coords="384,321, 392,321, 393,309, 407,307, 409,300, 410,304, 414,289, 409,300, 410,287, 422,287, 423,293, 429,279, 424,279, 422, 287, 410,287, 416,266, 419,264, 415,263, 411,260, 404,260, 409,258, 412,258, 412,255, 414,255, 415,258, 420,259, 425,252, 417,243, 409,241, 409,215, 427,192, 430,183, 425,180, 427,170, 442,161, 431,131, 412,121, 413,129, 402,129, 402,137, 397,137, 392,146, 395,150, 385,165, 382,187, 385,190, 383,195, 374,198, 374,230, 378,234, 378,238, 375,255, 371,257, 370,270, 367,266, 367,278, 372,278, 370,286, 382,304, 378,309, 382,315" />
	{/if}

	{if isset($scopedIdPs_cz)}
		<area href="javascript:$('#country_cz').submit();" alt="Česko" title="Česká republika" shape="poly" coords="396,416, 387,409, 381,400, 377,394, 380,396, 386,392, 397,385, 397,382, 401,385, 403,385, 405,382, 407,385, 417,388, 416,390, 420,394, 423,392, 422,390, 430,391, 430,394, 438,394, 441,401, 433,411, 426,413, 419,415, 406,412, 404,417" />
	{/if}

	{if isset($scopedIdPs_no)}
		<area href="javascript:$('#country_no').submit();" alt="Norge" title="Norge" shape="poly" coords="336,281, 324,273, 330,265, 327,263, 323,265, 323,260, 327,256, 325,256, 323,239, 332,238, 340,240, 338,238, 343,237, 340,236, 342,233, 339,236, 334,236, 323,237, 323,227, 337,213, 337,212, 338,210, 347,209, 350,208, 348,207, 349,205, 353,203, 363,188, 367,188, 366,184, 375,178, 371,178, 373,172, 375,157, 381,154, 379,153, 384,149, 384,140, 390,138, 387,135, 391,131, 386,132, 382,127, 391,123, 395,124, 398,123, 397,120, 394,122, 395,115, 402,113, 404,113, 403,107, 413,106, 414,104, 411,104, 411,101, 423,100, 421,96, 417,96, 423,92, 424,94, 428,92, 426,89, 431,86, 432,84, 435,85, 433,98, 437,86, 439,92, 441,83, 450,85, 452,84, 456,86, 460,87, 456,96, 463,97, 460,100, 456,104, 454,101, 448,96, 442,100, 440,115, 438,120, 431,119, 425,121, 416,114, 415,118, 412,118, 411,125, 413,129, 401,128, 402,136, 396,136, 392,145, 395,150, 390,163, 382,186, 384,190, 383,195, 376,195, 372,203, 373,221, 373,229, 378,233, 373,240, 376,246, 376,254, 372,256, 367,267, 362,257, 358,268, 344,280" />
	{/if}

	{if isset($scopedIdPs_fi)}
		<area href="javascript:$('#country_fi').submit();" alt="Suomi" title="Suomi" shape="poly" coords="454,247, 484,230, 491,216, 496,202, 498,190, 485,182, 486,174, 482,172, 482,167, 477,166, 478,161, 476,161, 475,155, 478,154, 465,139, 467,126, 457,121, 456,113, 455,103, 447,97, 444,101, 440,101, 439,104, 441,117, 438,121, 431,119, 430,122, 423,122, 416,114, 416,118, 412,120, 427,127, 443,161, 453,171, 433,206, 438,235, 453,245" />
	{/if}

	{if isset($scopedIdPs_dk)}
		<area href="javascript:$('#country_dk').submit();" alt="Danmark" title="Danmark" shape="poly" coords="375,330, 377,323, 380,323, 377,319, 379,313, 380,311, 374,311, 374,312, 370,312, 370,314, 366,316, 364,318, 360,317, 361,310, 363,311, 365,308, 365,304, 361,304, 361,298, 363,287, 361,289, 357,290, 355,295, 348,296, 345,298, 345,302, 347,302, 348,299, 352,299, 352,301, 347,304, 344,305, 343,319, 347,321, 347,328, 356,329, 355,326, 356,320, 359,325, 361,326, 366,326, 365,318, 366,316, 369,319, 368,321, 369,324, 373,324, 372,327, 369,328, 367,320, 369,331, 374,331, 378,328" />
	{/if}

	{if isset($scopedIdPs_nl)}
		<area href="javascript:$('#country_nl').submit();" alt="Nederland" title="Nederland" shape="poly" coords="321,383, 319,380, 307,377, 305,375, 303,379, 302,378, 300,379, 298,376, 300,376, 299,374, 301,371, 310,362, 310,354, 315,354, 315,362, 320,359, 318,354, 331,349, 336,351, 333,360, 330,360, 330,362, 332,364, 329,371, 322,372, 324,376" />
	{/if}

	{if isset($scopedIdPs_fr)}
		<area href="javascript:$('#country_fr').submit();" alt="France" title="France" shape="poly" coords="286,505, 273,505, 260,496, 259,498, 250,497, 250,496, 245,494, 244,492, 238,490, 235,485, 246,454, 247,444, 241,441, 238,435, 239,431, 236,428, 237,424, 231,422, 230,420, 222,416, 219,417, 216,413, 220,411, 219,409, 216,409, 216,405, 233,405, 237,410, 241,410, 242,411, 244,409, 249,410, 247,401, 242,401, 244,402, 239,402, 239,399, 242,401, 247,401, 247,394, 253,395, 254,400, 263,403, 266,401, 266,397, 280,392, 281,382, 290,380, 292,385, 295,385, 296,389, 299,390, 299,391, 304,393, 304,399, 310,396, 310,400, 315,405, 325,407, 330,411, 335,411, 341,414, 334,434, 328,436, 324,442, 322,442, 322,446, 319,449, 318,454, 322,450, 326,451, 325,460, 330,466, 327,468, 324,469, 328,474, 325,478, 328,482, 334,483, 332,488, 348,506, 343,508, 343,521, 348,523, 349,523, 350,523, 354,507, 350,504, 350,501, 343,508, 348,506, 332,488, 322,497, 313,499, 294,490, 286,494" />
	{/if}

	{if isset($scopedIdPs_be)}
		<area href="javascript:$('#country_be').submit();" alt="België/Belgique" title="België/Belgique" shape="poly" coords="319,404, 316,405, 315,403, 311,401, 311,396, 307,398, 305,397, 305,393, 300,391, 300,390, 297,390, 296,385, 292,385, 291,380, 297,377, 299,379, 302,378, 301,380, 304,379, 307,377, 310,377, 314,377, 314,379, 320,381, 319,387, 322,388, 324,391, 325,392, 323,395, 319,399, 320,402" />
	{/if}

	{if isset($scopedIdPs_ie)}
		<area href="javascript:$('#country_ie').submit();" alt="Éire" title="Éire" shape="poly" coords="215,351, 211,350, 203,350, 203,351, 198,352, 199,353, 196,352, 190,354, 182,354, 183,352, 180,351, 180,348, 178,348, 178,345, 181,344, 179,342, 180,340, 184,340, 187,338, 184,337, 190,336, 191,333, 196,330, 191,330, 191,326, 186,326, 185,323, 188,322, 189,321, 193,321, 191,319, 191,316, 190,314, 196,313, 198,316, 199,315, 202,317, 203,315, 207,315, 210,313, 213,312, 213,309, 216,307, 214,306, 207,305, 207,308, 206,309, 203,309, 203,311, 207,315, 213,321, 215,318, 217,318, 218,324, 223,325, 220,327, 222,331, 220,334, 220,340, 215,348" />
	{/if}

	{if isset($scopedIdPs_at)}
		<area href="javascript:$('#country_at').submit();" alt="Österreich" title="Österreich" shape="poly" coords="380,442, 369,443, 369,446, 364,446, 363,443, 360,445, 358,443, 355,442, 355,436, 357,435, 361,439, 363,435, 367,435, 368,436, 374,436, 374,434, 380,433, 385,433, 389,435, 386,430, 385,426, 391,422, 392,419, 395,420, 396,416, 398,419, 400,419, 402,418, 404,418, 406,416, 406,413, 414,413, 421,416, 422,414, 426,415, 425,420, 427,424, 428,429, 422,430, 424,433, 422,435, 423,437, 420,442, 419,445, 414,445, 414,446, 408,446, 405,450, 394,449, 382,447, 380,442" />
	{/if}

	{if isset($scopedIdPs_it)}
		<area href="javascript:$('#country_it').submit();" alt="Italia" title="Italia" shape="poly" coords="436,534, 432,543, 434,545, 439,548, 440,555, 434,557, 434,564, 429,571, 425,571, 422,568, 418,578, 420,584, 418,589, 411,589, 408,584, 401,583, 400,582, 396,581, 388,578, 387,571, 390,569, 390,571, 394,571, 395,569, 397,569, 401,571, 410,570, 413,568, 424,566, 426,563, 425,560, 430,558, 427,550, 424,547, 423,542, 422,541, 417,541, 413,539, 413,536, 411,533, 408,534, 406,532, 403,531, 399,525, 391,525, 390,524, 387,523, 356,535, 356,540, 356,542, 354,556, 348,555, 347,559, 343,560, 340,557, 341,546, 340,546, 340,536, 338,534, 338,530, 342,532, 350,527, 354,529, 358,536, 356,535, 387,523, 375,510, 371,509, 364,502, 361,493, 361,486, 354,484, 344,481, 337,488, 333,488, 334,483, 329,483, 327,481, 326,478, 328,474, 324,470, 324,469, 327,469, 329,466, 326,460, 329,459, 336,458, 340,454, 343,451, 343,454, 347,456, 349,460, 350,456, 351,453, 352,450, 354,453, 357,452, 359,454, 360,449, 363,450, 364,445, 369,447, 370,444, 380,443, 383,447, 397,449, 393,452, 395,454, 394,456, 395,460, 382,465, 385,471, 385,474, 382,476, 383,481, 386,485, 395,491, 398,494, 400,499, 403,507, 408,510, 413,514, 425,514, 427,514, 424,519, 434,523, 446,529, 453,536, 453,542, 448,540, 447,536, 438,535" />
	{/if}
	</map>
</div>
</div>
<div id="nren_sel_textual">
	<ul style="text-align: left; margin: 1em 0em 0em 3em; list-style-type: none">

		{if isset($scopedIdPs_be)}
			<li>
			<form id="country_be" action="{$disco_path}" method="{$scopeMethod_be}">
				{foreach from=$scopedIdPs_be item=scopedIdP}
					<input type="hidden" name="{$scopeKey_be}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="België/Belgique" />
			</form>
			</li>
		{/if}

		{if isset($scopedIdPs_cz)}
			<li>
			<form id="country_cz" action="{$disco_path}" method="{$scopeMethod_cz}">
				{foreach from=$scopedIdPs_cz item=scopedIdP}
					<input type="hidden" name="{$scopeKey_cz}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="Česko" />
			</form>
			</li>
		{/if}

		{if isset($scopedIdPs_dk)}
		<li>
			<form id="country_dk" action="{$disco_path}" method="{$scopeMethod_dk}">
				{foreach from=$scopedIdPs_dk item=scopedIdP}
					<input type="hidden" name="{$scopeKey_dk}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="Danmark" />
			</form>
		</li>
		{/if}

		{if isset($scopedIdPs_ie)}
		<li>
			<form id="country_ie" action="{$disco_path}" method="{$scopeMethod_ie}">
				{foreach from=$scopedIdPs_ie item=scopedIdP}
					<input type="hidden" name="{$scopeKey_ie}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="Éire" />
			</form>
		</li>
		{/if}

		{if isset($scopedIdPs_fr)}
		<li>
			<form id="country_fr" action="{$disco_path}" method="{$scopeMethod_fr}">
				{foreach from=$scopedIdPs_fr item=scopedIdP}
					<input type="hidden" name="{$scopeKey_fr}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="France" />
			</form>
		</li>
		{/if}

		{if isset($scopedIdPs_it)}
			<li>
			<form id="country_it" action="{$disco_path}" method="{$scopeMethod_it}">
				{foreach from=$scopedIdPs_it item=scopedIdP}
					<input type="hidden" name="{$scopeKey_it}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="Italia" />
			</form>
			</li>
		{/if}

		{if isset($scopedIdPs_nl)}
			<li>
			<form id="country_nl" action="{$disco_path}" method="{$scopeMethod_nl}">
				{foreach from=$scopedIdPs_nl item=scopedIdP}
					<input type="hidden" name="{$scopeKey_nl}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="Nederland" />
			</form>
			</li>
		{/if}

		{if isset($scopedIdPs_no)}
			<li>
			<form id="country_no" action="{$disco_path}" method="{$scopeMethod_no}">
				{foreach from=$scopedIdPs_no item=scopedIdP}
					<input type="hidden" name="{$scopeKey_no}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="Norge" />
			</form>
			</li>
		{/if}

		{if isset($scopedIdPs_fi)}
			<li>
			<form id="country_fi" action="{$disco_path}" method="{$scopeMethod_fi}">
				{foreach from=$scopedIdPs_fi item=scopedIdP}
					<input type="hidden" name="{$scopeKey_fi}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="Suomi" />
			</form>
			</li>
		{/if}

		{if isset($scopedIdPs_se)}
		<li>
			<form id="country_se" action="{$disco_path}" method="{$scopeMethod_se}">
				{foreach from=$scopedIdPs_se item=scopedIdP}
					<input type="hidden" name="{$scopeKey_se}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="Sverige" />
			</form>
		</li>
		{/if}

		{if isset($scopedIdPs_at)}
		<li>
			<form id="country_at" action="{$disco_path}" method="{$scopeMethod_at}">
				{foreach from=$scopedIdPs_at item=scopedIdP}
					<input type="hidden" name="{$scopeKey_at}" value="{$scopedIdP}" />
				{/foreach}
				<input type="submit" class="countrylist" value="Österreich" />
			</form>
		</li>
		{/if}
	</ul>
</div>

<script type="text/javascript">
		var linkTextGraphical = '{$l10n_link_graphicalview}';
		var linkTextTextual = '{$l10n_link_textualview}';

{literal}
		/* do not display the textual selection if the user has JavaScript enabled */
		$('#nren_sel_textual').toggle();
		$('#nren_sel_graphical').toggle();
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

</div>

</body>
</html>
