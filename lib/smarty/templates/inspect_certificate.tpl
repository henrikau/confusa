<a href="{$smarty.server.PHP_SELF}">
<img src="graphics/shape_flip_horizontal.png"
     title="Collapse detail view"
     alt=""
     class="url"/>
Collapse</a>

{if !isset($pem)}
	<div class="error">
	There were errors encountered when formatting the certificate. Here is a raw-dump.
	<pre class="certificate">
		{$certificate|escape}
	</pre>
	</div>
{else}
	<pre class="certificate">
		{$pem|escape}
	</pre>
{/if}
