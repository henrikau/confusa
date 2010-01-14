<?php /* Smarty version 2.6.22, created on 2009-07-17 16:52:51
         compiled from tools.tpl */ ?>

<h3>{$l10n_heading_tools}</h3>
<hr style="width: 90%"/>
<br />
<p class="info">
{$l10n_text_explanation1}
</p>

<br />
<hr style="width: 90%"/>
<br />
<h4>create_cert.sh</h4>
<p class="info">
{$l10n_text_explanation2}
</p>
<p class="info">
{$l10n_text_explanation3}
</p>
<p class="info">
{$l10n_text_explanation4}
</p>
<pre>
       bash create_cert.sh -new_no_push
</pre>
<br />
<form method="get" action="tools.php">
  <p>
    <input type="hidden" name="send_file" value="0" />
    <input type="submit" value="{$l10n_download_certscript}" />
  </p>
</form>

<br />
<hr style="width: 90%"/>
