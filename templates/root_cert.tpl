<h3>{$l10n_heading_rootca}</h3>
<hr style="width: 80%" />
<p class="info">
{$l10n_text_rootcaexpl1}
</p>
<ul style="margin-left: 18px">
<li>
	{$l10n_text_rootcaexpl2} <a href="root_cert.php?show_root_cert=yes&amp;{$ganticsrf}">{$l10n_text_here}</a>.
</li>
<li>
	<a href="{$ca_download_link}">{$l10n_link_direct}</a> {$l10n_text_rootcaexpl3}
</li>
<li>
	<a href="root_cert.php?send_file=cacert&amp;{$ganticsrf}">{$l10n_button_download}</a> {$l10n_text_downlcacert}
<li>
	<a href="root_cert.php?send_file=cachain&amp;{$ganticsrf}">{$l10n_button_download}</a> {$l10n_text_downlchain}
</li>
</ul>

<div class="spacer"></div>
<div class="spacer"></div>

<h3>CRL</h3>
<hr style="width: 80%" />
<p class="info">
{$l10n_text_crlexpl1}
</p>

<ul style="margin-left: 18px">
<li>{$l10n_text_crlexpl2}
<a href="root_cert.php?show_crl=yes&amp;{$ganticsrf}">{$l10n_text_here} </a></li>
<li>{$l10n_text_crldili} <a href="{$crl_download_link}">{$l10n_text_here}</a>.</li>
<li>
	<a href="root_cert.php?send_file=crl&amp;{$ganticsrf}">{$l10n_button_download}</a> {$l10n_text_downlcrl}
</li>
</ul>

<br />
{if isset($ca_dump)}
<br />
<h4>{$l10n_heading_cadump}</h4>
<hr style="width: 80%" />
<pre class="certificate">
{$ca_dump|escape}
</pre>
{/if}

{if isset($crl_dump)}
<br />
<h4>{$l10n_heading_crldump}</h4>
<hr style="width: 80%" />
<pre class="certificate">
{$crl_dump|escape}
</pre>
<hr style="width: 80%" />
{/if}
