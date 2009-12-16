<?php /* Smarty version 2.6.22, created on 2009-07-17 16:52:51
         compiled from tools.tpl */ ?>

<h3>{$heading}</h3>
<hr />
<br />
<p class="info">
{$tools_explanation1}
</p>

<BR />
<HR />
<H4>create_cert.sh v0.1</H4>
Tool for creating key, CSR and general filetransfer with the server. This
can be downloaded either directly through the browser, or, you can receive it via email.<br />
<B>Note</B> this is a strictly <I>personal</I> script, and it is tailored to your user-credentials.
This means that you cannot use someone else's script, nor they yours.
<br />
<br />

<table width="30%">
  <tr>
    <td>
      <form method="GET" action="">
      <input type="hidden" name="send_email">
      <input type="submit" value="Send as email">
      </form>
    </td>
    <td> </td>
    <td>
      <form method="GET" action="tools.php">
      <input type="hidden" name="send_file">
      <input type="submit" value="Direct download">
      </form>
    </td>
  </tr>
</table>  
<HR />

{if $person->isSubscriberAdmin()}
<br />
<hr style="width: 90%" />
<h4>XML_Client</h4>
<p class="info">
  XML_Client is a library tool for connecting to the Robotic
  interface. Specific information can be found in
  the <a href="robot.php?mode=admin&robot_view=info">RI Section</a>
</p>
<form method="GET" action="tools.php">
  <input type="hidden" name="xml_client_file">
  <input type="submit" value="Download file">
</form>
{/if}
