<h3>Confusa</h3>
<br />
<p>
	Confusa is a web-service that maps a federated identity into an X.509 certificate.<br />
</p>
<br />

<p>
	The aim of Confusa is to help you obtain a <span style="cursor:help; border-bottom: dashed 1px"
	title="X.509 certificates help 3rd parties to verify your identity.
	They are like digital ID cards, containing a statement about your identity by a trusted organization.">
	X.509 certificate</span> very fast using these two steps:
</p>
	<ul style="margin-left: inherit">
	<li>Logging in to your home institution</li>
	<li>Uploading a certificate request</li>
	</ul>
<br />
<p>
	The home institution will relay information about you (name, email, organization) to Confusa.
	This information and your certificate request will permit Confusa and a backend
	Online-CA to issue a X.509 certificate to you.
</p>

<br />
<h4>Why does it work?</h4>

<p>The basis for it is trust. We trust your home institution to autheniticate you correctly and
the Online-CA trusts Confusa to do its job properly. Your home institution already identified
you when you signed up with it, so why verify the same information twice or three times?</p>
<br />

<h4>How long are the certificates valid?</h4>

<p>Certificates issued by Confusa are valid for 13 months.</p>
<br />

<h4>What do I need to do?</h4>

<p>Just login with your identity provider (usually your home institution) to be
able to take advantage of Confusa's functionality.</p>
<br />

<h4>Does Confusa store my private data?</h4>

<p>Privacy has been a very big concern in the design of Confusa.
When you login, Confusa doesn't store <b>any</b> data about you! Only if you
get a certificate issued by Confusa, Confusa and/or the Online-CA store the
subject name of that certificate.
Such a subject name usually contains your country, organization and
full name. We <b>have</b> to store that information.</p>
