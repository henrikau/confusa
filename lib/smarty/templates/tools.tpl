<?php /* Smarty version 2.6.22, created on 2009-07-17 16:52:51
         compiled from tools.tpl */ ?>

<h3>{$heading}</h3>
<hr style="width: 90%"/>
<br />
<p class="info">
{$tools_explanation1}
</p>

<br />
<hr style="width: 90%"/>
<br />
<h4>create_cert.sh</h4>
<p class="info">
Tool for creating key, CSR and general filetransfer with the
server. This can be downloaded either directly through the browser, or,
you can receive it via email.
</p>
<p class="info">
<b>Note</b> this is a strictly <i>personal</i> script, and it is
tailored to your user-credentials.  This means that you cannot use
someone else's script, nor they yours.
</p>
<p class="info">
<b>Note:</b> this tool has become <b>deprecated</b>. It is still
available for those who do not want to create their own CSR
manually. Download and use it the following way:
</p>
<pre>
       bash create_cert.sh -new_no_push
</pre>
<br />
<form method="get" action="tools.php">
  <p>
    <input type="hidden" name="send_file" value="0" />
    <input type="submit" value="Download script" />
  </p>
</form>

<br />
<hr style="width: 90%"/>
