<?php /* Smarty version 2.6.22, created on 2009-07-17 16:52:51
         compiled from tools.tpl */ ?>

<h2>Certificate Revocation Area</h2>

<h3>Tools of the trade</h3>
This is where you find the tools needed when using Confusa.
<hr />
<H4>create_cert.sh v0.1</H4>
Tool for creating key, CSR and general filetransfer with the server. This
can be downloaded either directly through the browser, or, you can receive it via email.<br />
<B>Note</B> this is a strictly <I>personal</I> script, and it is tailored to your user-credentials.
This means that you cannot use someone else's script, nor they yours.
<br />
<br />
<?php if ($this->_tpl_vars['email_sent']): ?>
<div class="success">
	Mail sent to <?php echo $this->_tpl_vars['person']->get_email(); ?>
 with new version of create_cert.sh
</div>
<?php endif; ?>
<table width="30%">
  <tr>
    <td>
      <form method="GET" action="tools.php">
      <input type="hidden" name="send_email">
      <input type="submit" value="Send as email">
      </form>
    </td>
    <td>
      <form method="GET" action="tools.php">
      <input type="hidden" name="send_file">
      <input type="submit" value="download">
      </form>
    </td>
  </tr>
</table>  
<HR>