<h3>Root CA</h3>

<p>
This is the Certificate we use for signing the CSRs we receive.
If you want the certificate, it can be downloaded from <a href="{$ca_file}">here</a>
</p>

<p>
Or, if you want to download it directly, press here:
</p>

<form method="get" action="root_cert.php">
<div>
<input type="hidden" name="send_file" value="" />
<input type="submit" name="submit" value="Download" />
</div>
</form>

<hr />
<br />
<pre>
{$ca_dump}
</pre>
