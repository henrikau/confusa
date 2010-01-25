<h4>XML_Client library</h4>
<p class="info">
  {$l10n_infotext_xmlclient1}
</p>

<p class="info">
  {$l10n_infotext_xmlclient2}
</p>

<p class="info">
  {$l10n_infotext_xmlclient3}
</p>

<p class="info">
  {$l10n_infotext_xmlclient4}
</p>
<pre>
       from Confusa_Client import Confusa_Client
</pre>
<br />
<p>
  {$l10n_infotext_xmlclient5}
</p>

<p class="info">
  {$l10n_infotext_xmlclient6}
</p>
<pre>          {$ri_path}</pre>

{if $person->isSubscriberAdmin()}
<br />

<br />

<form method="get" action="tools.php">
  <p>
    <input type="hidden" name="xml_client_file" />
    <input type="submit" value="{$l10n_button_downloadfile}" />
  </p>
</form>
{/if}
