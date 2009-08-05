{if !isset($pem)}
	<DIV class="error">
	There were errors encountered when formatting the certificate. Here is a raw-dump.
	<PRE>
		{$certificate}
	</PRE>
	</DIV>
{else}
	<pre>
		{$pem}
	</pre>
{/if}
