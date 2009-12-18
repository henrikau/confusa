<h4>XML_Client library</h4>
<p class="info">
  To use the Robotic Interface (RI), you must upload a certificate to
  the portal. Once this is done, you can use this to communicate with
  the RI.
</p>

<p class="info">
  XML_Client is a library tool for connecting to the Robotic
  interface. Specific information can be found in
  the <a href="robot.php?robot_view=info">RI Section</a>
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
       from Confusa_Client import Confusa_Client
</pre>
<br />
<p>
  You should also look in the README-file for a short code-example.
</p>

<p class="info">
  When configuring the script, point the url to
</p>
<pre>          {$ri_path}</pre>

{if $person->isSubscriberAdmin()}
<br />

<br />

<form method="get" action="tools.php">
  <p>
    <input type="hidden" name="xml_client_file" />
    <input type="submit" value="Download file" />
  </p>
</form>
{/if}
