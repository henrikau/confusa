<h3>Root CA</h3>
<hr width="80%" />
<p class="info">
This is the Certificate we use for signing the CSRs we receive.
</p>
<br />
<ul>
<li>
	To view the certificate in the browser, press <a href="root_cert.php?show_root_cert=yes">here</a>.
</li>
<li>
	{* FIXME *}
	It can be installed directly into the browser via <b>this
	link</b>.
</li>
<li>
	<a href="{$ca_file}">Direct link</a> to the certificate.
</li>
<li>
	<form method="get" action="root_cert.php">
		<input type="hidden" name="send_file" value="cacert" />
		If you want to download it directly: 
		<input type="submit" name="submit" value="Download" />
	</form>
</li>
</ul>

<br />
<p class="info">
</p>

<br />

<h3>CRL</h3>
<hr width="80%" />
<p class="info">
The CRL (Certificate Revocation List) is a list of all revoked (invalid)
certificates published by this CA.
</p>
<ul>
<li>View the CRL in the brower: 
<a href="root_cert.php?show_crl=yes">here </a></li>
<li>Direct link to crl: <a href="{$crl_file}">here</a>.</li>
<li>
	<form method="get" action="root_cert.php">
		<input type="hidden" name="send_file" value="crl" />
		If you want to download it directly: 
		<input type="submit" name="submit" value="Download" />
	</form>
</li>
</ul>

<br />
{if $ca_dump}
<br />
<h4>CA certificate dump:</h4>
<hr width="80%">
<pre>
{$ca_dump}
</pre>
{/if}

{if $crl_dump}
<br />
<h4>CRL dump:</h4>
<hr width="80%">
<pre>
{$crl_dump}
</pre>
<hr width="80%" />
{/if}