<div class="csr">
  <fieldset>
    <legend>Upload new CSR</legend>
    <br />
    <p class="info">
      Upload a local CSR for signing by the CA. If you created
      this with any globus-specific tools, you should look for
      the folder ".globus" in you home directory.
    </p>
    <br />
    <table>
      <tr>
	<td>
	  <form action="process_csr.php" method="post" enctype="multipart/form-data">
	    <div><!-- XHTML strict won't allow inputs just within forms -->
	      <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
	      <input type="file" name="user_csr" />
	      <input type="submit" value="Upload CSR" />
	    </div>
	  </form>
	</td>
      </tr>
    </table>
  </fieldset>

<div class="spacer"></div>

<fieldset>
<legend>Apply for a certificate in browser</legend>
<div id="info_view">
	Press the start button to generate a certificate request in your browser
</div>

<br />
<form name="reqForm" id="reqForm" onSubmit="return createRequest('{$dn}', {$keysize});" method="post" action="process_csr.php">
<input id="reqField" type="hidden" name="browserRequest" value="" />
<input type="submit" name="Send" id="startButton" value="Start" />
</form>
</fieldset>

{if isset($order_number)}
<script type="text/javascript">
	{* No need to press "Start" once the order number is set *}
	document.getElementById("startButton").setAttribute("style","display: none");
	{if $done === TRUE}
		statusDone({$order_number});
	{else}
		{* refresh the page all ten seconds, and update the processing label all 2 seconds *}
		setTimeout('window.location="{$status_poll_endpoint}";', 10000);
		pollStatus('Processing order number {$order_number}.');
	{/if}
</script>
{/if}

{* This part will be JavaScript or another script executable by the browser (ActiveX?) *}
{if isset($deployment_script)}
	{$deployment_script}
{/if}
</div> <!-- upload csr -->
