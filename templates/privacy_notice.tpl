<h2>{$l10n_heading_privnotice}</h2>
<div class="spacer"></div>
{if isset($nren_pt_text)}
{$nren_pt_text}
{else}
<br />
<br />
<br />
<p>
  The current privacy notice that applies to <b>you</b> is crafted per
  NREN and per portal, and cannot be displayed before we know your
  institution. In other words, you will have to log in.This is a
  chicken-and-hen problem and we are working to get around this problem.
</p>
<p>
  In short, the portal does the following with your data.
</p>
<ul>
  <li>Receive Name, email, affiliation, unique personal identifier,
    country and entitlement from your IdP.</li>
  <li>Use the information to decorate a certificate request (CSR) which
    will be turned into your certificate.</li>
  <li>The CSR is sent off to a third party CA which <b>may</b> be
    located elsewhere. Depending on the configuration of the portal etc,
    this information <b>may</b> travel overseas or across country
    borders.</li>
  <li>The CA will keep a copy of the certificate (with your personal
    details in them) as well as logs containing time of issue etc.</li>
  <li>The portal logs that a new certificate has been issued to you, and
    this is logged as a timestamp, your unique identifier and your
    IP-address.</li>
</ul>
{/if}
