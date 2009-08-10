<script type="text/javascript">
{literal}
//<![CDATA[
function collapse(div) {
	div.style.display = 'none';
}

function toggleExpand(doc) {
	/* Check if the needed DOM functionality is available */
	if (document.getElementById) {
		var focus = doc.firstChild;
		focus = doc.firstChild.innerHTML?doc.firstChild:doc.firstChild.nextSibling;
		focus.innerHTML = focus.innerHTML=='+'?'-':'+';
		focus = doc.parentNode.nextSibling.style?
			doc.parentNode.nextSibling:
			doc.parentNode.nextSibling.nextSibling;
		focus.style.display = focus.style.display=='block'?'none':'block';

	}
}

	/* Call this in the beginning. If the user does not have JavaScript, the
	items will remain expanded and if the user agent has JavaScript they will
	be collapsed. */
function collapseAll() {
	var divs = document.getElementsByTagName('div');

	for (var i = 0; i < divs.length; i++) {
		if (divs[i].id.indexOf('expdiv') > -1) {
			collapse(divs[i]);
		}
	}
}
//]]>
{/literal}
</script>

<h3>Confusa</h3>
<br />
<p>
	Confusa is a web-service that maps a <span class="wtf" title="In the Internet, a federated identity
	means that information about a the identity of a person is exchanged automatically between institutions,
	without further human verification. Imagine Harry P. has login data for university Ravenclaw, but not for university Gryffidor, but he wants to use the electronic library of university Gryffidor. With his federated identity he can login with Ravenclaw to use the services at Gryffidor, because Ravenclaw will tell Gryffidor 'Harry P. has authenticated his identity'.">federated identity</span>
	into an X.509 certificate.<br />
</p>
<br />

<p>
	The aim of Confusa is to help you obtain a <span class="wtf"
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
	<span class="wtf" title="A Certificate Authority (CA) is an organization that issues certificates for people, just like e.g. the state issues ID-cards in the real world. Not everybody can do that, since the organization has to be trusted by service providers.">Online-CA</span> to issue a X.509 certificate to you.
</p>

<br />
<h4><a href="javascript:void(0)" class="exphead" onclick="toggleExpand(this)"><span class="expchar">+</span> Why does it work?</a></h4>
<div id="expdiv1" style="expcont">
<p>The basis for it is trust. We trust your home institution to autheniticate you correctly and
the Online-CA trusts Confusa to do its job properly. Your home institution already identified
you when you signed up with it, so why verify the same information twice or three times?</p>
</div>
<br />

<h4><a href="javascript:void(0)" class="exphead" onclick="toggleExpand(this)"><span class="expchar">+</span> How long are the certificates valid?</a></h4>
<div id="expdiv2" style="expcont">
<p>Certificates issued with Confusa are valid for 13 months.</p>
</div>
<br />

<h4><a href="javascript:void(0)" class="exphead" onclick="toggleExpand(this)"><span class="expchar">+</span> Why do I have to login?</a></h4>
<div id="expdiv3" style="expcont">
<p>We need your identity to be able
to issue certificates to you - and by using your institution login, we spare us and
you the time and effort of another identity verification procedure.</p>
</div>
<br />

<h4><a href="javascript:void(0)" class="exphead" onclick="toggleExpand(this)"><span class="expchar">+</span> Does Confusa store my private data?</a></h4>
<div id="expdiv4" style="expcont">
<p>Privacy has been a very big concern in the design of Confusa.
When you login, Confusa doesn't store <b>any</b> data about you! Only if you
get a certificate issued by Confusa, Confusa and/or the Online-CA store the
subject name of that certificate.
Such a subject name usually contains your country, organization and
full name. We <b>have</b> to store that information.</p>
</div>

<script type="text/javascript">collapseAll()</script>
