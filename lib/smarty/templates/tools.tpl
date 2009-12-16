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
  <input type="hidden" name="send_file" value="0">
  <input type="submit" value="Download script">
</form>
  <br />
<hr style="width: 90%"/>

{if $person->isSubscriberAdmin()}
<br />
<h4>XML_Client library</h4>
<p class="info">
  XML_Client is a library tool for connecting to the Robotic
  interface. Specific information can be found in
  the <a href="robot.php?mode=admin&robot_view=info">RI Section</a>
</p>
<p class="info">
  You will have to write the wrapper and your local logic for this, but
  the library will handle SSL and X.509 authentication for you. Download
  the zip-archive and follow the instructions in the README.
</p>
<p class="info">
  To use the library, add the following lines to your python-script.
</p>
<pre>
       from XML_Client import XML_Client
       from Confusa_Parser import ConfusaParser
</pre>
<br />
<form method="get" action="tools.php">
  <input type="hidden" name="xml_client_file">
  <input type="submit" value="Download file">
</form>
{/if}
