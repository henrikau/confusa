{if !isset($pem}
There were errors encountered when formatting the certificate. Here is a raw-dump.<br />

<pre>
{$certificate}
</pre>

{else}

[ <a href="download_certificate.php?email_cert={$authKey}">Email</a> ] 
[ <a href="download_certificate.php?file_cert={$authKey}">Download</a> ] 
[ <strong>Inspect</strong> ] 
{if $standalone}
[ <a href="download_certificate.php?delete_cert={$authKey}">Delete</a> ] 
<pre>
{$pem}
</pre>
{/if}