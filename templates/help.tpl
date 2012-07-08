<h2>{$l10n_heading_help}</h2>
<div class="spacer"></div>

{if isset($nren_help_text)}
{* ------------------------------
 * NREN specific help
 * ------------------------------*}
<div class="spacer"></div>
<h3>{$nren}{$l10n_heading_nrenadvice}</h3>
{$nren_help_text}

{elseif isset($nren_unset_help_text) && isset($nren_contact_email)}
{* ------------------------------
 * NREN-help not set, post message to user to poke them
 * ------------------------------*}
{$nren_unset_help_text|escape}
<p class="center">
  <a href="mailto:{$nren_contact_email|escape}">{$nren_contact_email|escape}</a>
</p>
<div class="spacer"></div>
{/if}

{* Start of generic help, this will be posted regardless of user is
AuthN or not
*}
<fieldset>
<legend>FAQ</legend>
<div class="spacer"></div>


<a href="#"  class="eh_head" id="faq1">{$index_faq_heading1}</a>
<div class="eh_toggle_container">{$index_faq_text1}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="faq2">{$index_faq_heading2}</a>
<div class="eh_toggle_container">{$index_faq_text2}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="faq3">{$index_faq_heading3}</a>
<div class="eh_toggle_container">{$index_faq_text3}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="faq4">{$index_faq_heading4}</a>
<div class="eh_toggle_container">{$index_faq_text4}</div>
<div class="spacer"></div>

<a class="eh_head" href="#" id="faq5">{$index_faq_heading5}</a>
<div class="eh_toggle_container">{$index_faq_text5}</div>
<div class="spacer"></div>

{* extra help, suppress it perhaps? NOT translated *}

<a href="#"  class="eh_head" id="toc_csr_create">How do I create a CSR?</a>
<div class="eh_toggle_container">
  <p>The easiest way is to use the browser directly.</p>

  <p>
    If you <b>really</b> need to use the command-line, you should
      consider using the <a href="http://www.openssl.org">OpenSSL</a>
      toolkit for the creation of certificate signing requests. OpenSSL
      comes pre-installed with most Linux distributions and MacOS X. The
      OpenSSL project also offers
      an <a href="http://www.openssl.org/related/binaries.html"> OpenSSL
      for Windows</a> download.
  </p>
  
  <p>
    Once you have OpenSSL installed on your platform, use it to generate
    a CSR by typing:
  </p>
  <p class="example">
    openssl req -new -newkey rsa:2048 -keyout userkey.pem -out usercert_request.pem -subj /CN=bla
  </p>

  <ul class="help">
    <li>
      The generated file <b>userkey.pem</b> is your private key and
      should during all the validity time of the certificate be kept by
      you and <b>only</b> you.
    </li>
    <li>
      You should also define a <span class="wtf" title="more than 8
      characters, not in the dictionary, include numbers"> strong
      password</span> when asked for it.
    </li>
    <li>
      The file, which you upload to Confusa to request a certificate
      is <b>usercert_request.pem</b>.
    </li>
</ul>
  <b>Note</b>: If Confusa uses the Comodo-CA, the subject of the request
  can be arbitrary, as shown above, because Confusa will replace it with
  real values matching you, once it issues the certificate!
</div><div class="spacer"></div>


<a href="#" class="eh_head" id="toc_supported_browsers">
  Which browsers are supported for browser requests
</a>
<div class="eh_toggle_container">

<ul class="help">
<li>Mozilla based browsers (Firefox, SeaMonkey)</li>
<li>Internet Explorer (on Windows XP, Vista, 7)</li>
<li>Apple Safari (on MacOS X)</li>
<li>Google Chrome (on Windows, Linux)</li>
<li>Opera</li>
</ul>

These browsers are <b>not</b> supported at the moment:
<ul class="help">
<li>Apple Safari on the iPhone/iPad</li>
<li>Internet Explorer on old Windows versions</li>
<li>Google Chrome on MacOS X</li>
</ul>
</div><div class="spacer"></div>

<a href="#" class="eh_head" id="toc_export">
  How to export certificates from the keystore
</a>
<div class="eh_toggle_container">
  <b>Firefox:</b>
  <ul class="help">
    <li>Go to Preferences</li>
    <li>- Advanced</li>
    <li>- View Certificates</li>
    <li>- Your certificates</li>
    <li>Then press "Backup" on the certificate you want to
      backup, which will create a PKCS#12 file on your harddisk.</li>
    </ul>

  <b>Opera:</b>
  <ul class="help">
    <li>Go to Preferences</li>
    <li> - Advanced</li>
    <li> - Security</li>
    <li> - Manage Certificates</li>
    <li> Select your certificate and click "Export". </li>
    <li>Select PKCS#12 format.</li>
  </ul>

  <b>Internet Explorer:</b>
  <ul class="help">
    <li> Go to Internet</li>
    <li> - Options</li>
    <li> - Content</li>
    <li> - Certificates</li>
    <li> Click the certificate you want to export and click "export".</li>
    <li> Confirm that you want to include the private key</li>
    <li> Select PKCS#12 format and "enable strong protection".</li>
  </ul>
  <b>Safari/Mac OS X:</b>
  Your certificate should be automatically added to the keyring.
  <br /><br />
  <b>Chrome/Windows:</b>It works the same way as with Internet Explorer.
</div>
<div class="spacer"></div>

<a href="#" class="eh_head" id="toc_pkcs12">
  How to convert between PKCS#12 and X.509
</a>
<div class="eh_toggle_container">
If you have a PKCS#12 certificate but want it in PEM format (e.g. for
Grid job submission), do the following:
<br />
<br />
Private key:
<p class="example">
  openssl pkcs12  -nocerts -in cert.p12 -out ~user/.globus/userkey.pem
</p>
<br />
Public key:
<p class="example">
  openssl pkcs12 -clcerts  -nokeys -in cert.p12 -out ~user/.globus/usercert.pem
</p>
<br />
Subsequently, give them the right permissions:<br />
<p class="example">
  chmod 0600 userkey.pem<br />
  chmod 0644 usercert.pem
</p>
</div><div class="spacer"></div>

<a href="#" class="eh_head" id="toc_export_chrome_linux">
  How to export certificates from the keystore (Chrome/Linux)
</a>
<div class="eh_toggle_container">
<p>
  Instead of having it's own certificate management facilities, on Linux
  Google chrome ties into libnss3-tools. See
  the <a href="http://code.google.com/p/chromium/wiki/LinuxCertManagement">
    Chrome documentatio</a>n on certificate management. On
  the downside, users will not have a very nice graphical interface to
  manage their certificates. On the other downside, the certutil command
  is not very easy to use. On the upside, it is rather powerful.
</p>
<p>
  So after issuing a certificate, it can be checked whether it is
  present as one of the user's certificates:
</p>
<p class="example">
  certutil -d sql:$HOME/.pki/nssdb -L
</p>
<p>
  Unfortunately the auto-generated cert id makes all cert operations an
  input-hazzle. List the details of a certificate:
</p>
<p class="example">
  certutil -d sql:$HOME/.pki/nssdb -L -n Confusa\ Test\ User\ Full\
  Name\ aeoeaa\ confusatest@feide.no\'s\ TERENA\ eScience\ Personal\ CA\
  ID
</p>
<p>
  To export the certificate, from the NSS3 database, pk12util has to be
  used because certutil can only export the public key:
</p>
<p class="example">
  pk12util -d sql:$HOME/.pki/nssdb -o cert.p12 -n Confusa\ Test\ User\
  Full\ Name\ aeoeaa\ confusatest@feide.no\'s\ TERENA\ eScience\
  Personal\ CA\ ID
</p>
<p>Now that the certificate has been exported in PKCS#12 format, it
  needs to be <a href="#pkcs12">converted to PKCS#7 format</a> in order
  to be used with the Globus/Grid software.
</p>
</div>
<div class="spacer"> </div>

<a href="#" class="eh_head" id="toc_import_chrome">
  How to import CA certificates into Chrome
</a>
<div class="eh_toggle_container">
  Automatic import via the Chrome browser does not work. Instead the
  certificate has to be downloaded to the harddrive. Opening the browser
  options, navigating to "Under the hood" and clicking on the
  "Certificate Management" button, will bring up Windows' integrated
  certificate management. There the certificate can be imported.
</div><div class="spacer"></div>


<a href="#" class="eh_head" id="toc_import_ca">
  Importing the CA certificate (Linux)
</a>
<div class="eh_toggle_container">
  Our friend CertUtil will have to help us with importing again. First
  we download the CA-cert from the CA section of the portal and then we
  import it with certutil. -t T,c,c tells cert-util that the certificate
  can serve as a well CA for client certificates in SSL and is a valid
  CA for S/MIME and JAR.
<p class="example">
  certutil -d sql:$HOME/.pki/nssdb -A -n terena_escience_ca -t T,c,c -i
  TERENAeSciencePersonalCA.crt
</p>
</div><div class="spacer"></div>


<script type="text/javascript">
	$("#faq1").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading1}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading1}"); {literal}}{/literal}
	);
	$("#faq2").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading2}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading2}"); {literal}}{/literal}
	);
	$("#faq3").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading3}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading3}"); {literal}}{/literal}
	);
	$("#faq4").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading4}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading4}"); {literal}}{/literal}
	);
	$("#faq5").toggle(
		function () {literal}{{/literal}
			$(this).html("{$index_faq_heading5}"); {literal}}{/literal},
		function() {literal}{{/literal}
			$(this).html("{$index_faq_heading5}"); {literal}}{/literal}
	);
	$("#toc_csr_create").toggle(
	function () {literal}{{/literal}
		$(this).html("How do I create a CSR?");
		{literal}}{/literal},
	function () {literal}{{/literal}
		$(this).html("How do I create a CSR?");
	{literal}}{/literal}
	);

	$("#toc_supported_browsers").toggle(
	function () {literal}{{/literal}
		$(this).html("Which browsers are supported for browser requests");
		{literal}}{/literal},
	function () {literal}{{/literal}
		$(this).html("Which browsers are supported for browser requests");
	{literal}}{/literal}
	);

	$("#toc_export").toggle(
	function () {literal}{{/literal}
		$(this).html("How to export certificates from the keystore");
		{literal}}{/literal},
	function () {literal}{{/literal}
		$(this).html("How to export certificates from the keystore");
	{literal}}{/literal}
	);

	$("#toc_pkcs12").toggle(
	function () {literal}{{/literal}
		$(this).html("How to convert between PKCS#12 and X.509");
		{literal}}{/literal},
	function () {literal}{{/literal}
		$(this).html("How to convert between PKCS#12 and X.509");
	{literal}}{/literal}
	);

	$("#toc_export_chrome_linux").toggle(
	function () {literal}{{/literal}
		$(this).html("How to export certificates from the keystore (Chrome/Linux)!");
		{literal}}{/literal},
	function () {literal}{{/literal}
		$(this).html("How to export certificates from the keystore (Chrome/Linux)!");
	{literal}}{/literal}
	);


	$("#toc_import_chrome").toggle(
	function () {literal}{{/literal}
		$(this).html("How to import CA certificates into Chrome");
		{literal}}{/literal},
	function () {literal}{{/literal}
		$(this).html("How to import CA certificates into Chrome");
	{literal}}{/literal}
	);

	$("#toc_import_ca").toggle(
	function () {literal}{{/literal} $(this).html("Importing the CA certificate (Linux)"); {literal}}{/literal},
	function () {literal}{{/literal} $(this).html("Importing the CA certificate (Linux)"); {literal}}{/literal}
	);
</script>

</fieldset>
