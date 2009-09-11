// create a CRMF request and a respective key

var crmf="";
var timer="";

function checkWindowsRequest(request) {
    with (document.reqForm) {
	browserRequest.value = request;
	submit();
    }
}

/**
 * This is mostly about visual feedback while the certificate is still
 * processed
 */
function pollStatus(message) {
	var info_view = document.getElementById("info_view");

	info_view.innerHTML = message + "<br /><br />Please don't close the window " +
	"and wait until processing is finished. That will take, depending on load " +
	"about <b>2 minutes</b>. Then, install the certificate.<br /><br />" +
	"If something goes wrong, you can install the certificate later from the " +
	"download area!";
	var numDots = message.substring(message.indexOf("."), message.length).length;

	if (numDots >= 5) {
	    var newMessage = message.substring(0, message.indexOf(".") + 1);
	} else {
	    var newMessage = message + ".";
	}

	timer = window.setTimeout("pollStatus('" + newMessage + "')", 2000);
}

/**
 * Call this method when processing is done. It will adjust the HTML elements to forward the
 * progress information to the user and clear the timer.
 */
function statusDone(key) {
    window.clearTimeout(timer);
    var install_text = "<b>Done</b> processing! Please <a href=\"process_csr.php?install_cert=" +
     + key + "\">install your certificate!</a>";
    document.getElementById("info_view").innerHTML = install_text;
    document.getElementById("reqForm").style.display = "none";
}

function createIEVistaRequest(dn, keysize)
{
    try {
	var classFactory = new ActiveXObject("X509Enrollment.CX509EnrollmentWebClassFactory");

	// Declaration of the objects
	var objEnroll = classFactory.CreateObject("X509Enrollment.CX509Enrollment");
	var objPrivateKey = classFactory.CreateObject("X509Enrollment.CX509PrivateKey");
	var objRequest = classFactory.CreateObject("X509Enrollment.CX509CertificateRequestPkcs10");
	var objDN = classFactory.CreateObject("X509Enrollment.CX500DistinguishedName");

	// Specify the name of the cryptographic provider.
	objPrivateKey.ProviderName = "Microsoft Enhanced RSA and AES Cryptographic Provider";

	/* allow signing */
	objPrivateKey.KeySpec = 0x2;
	objPrivateKey.Length=keysize;
	/* allow archival and plaintext export */
	objPrivateKey.ExportPolicy = 0x1;
	/* use "RSA-full" as the key algorithm */
	objPrivateKey.ProviderType = "1";
	objRequest.InitializeFromPrivateKey(1, objPrivateKey, "");
	objDN.Encode(dn, 0);
	objRequest.Subject = objDN;
	objEnroll.InitializeFromRequest(objRequest);
	request = objEnroll.CreateRequest(1);
    } catch (e) {
	var message="Hit the following error upon key generation: " + e.description
	+ "\nIs the Confusa instance configured as a trusted site?";
	alert(message);
	return false;
    }

    checkWindowsRequest(request);
    return false;
}

function createIEXPRequest(dn, keysize)
{
    var info_view = document.getElementById("info_view");
    /* don't remove that, because IE will need that *on* the page to be able
     * to address XEnroll */
    info_view.innerHTML= info_view.innerHTML + "<OBJECT id=\"XEnroll\"\n" +
        "classid=\"clsid:127698e4-e730-4e5c-a2b1-21490a70c8a1\"" +
        "codebase=\"xenroll.dll\"></OBJECT>";

    XEnroll.Reset();
    XEnroll.ProviderType = 1;
    /* Note that the "base provider" will only allow for RSA keys with a maximum
     * of 512 bits due to former export restrictions - therefore use the
     * enhanced cryptographic provider */
    XEnroll.ProviderName = "Microsoft Enhanced Cryptographic Provider v1.0";
    /* create the key with the right keysize (upper 16 bits)
     * flag 1 (crypt_exportable) as the export policy for the private key */
    XEnroll.GenKeyFlags=(keysize<<16)+1;
    XEnroll.HashAlgID = 0x8004;
    XEnroll.KeySpec = 1;
    var request = XEnroll.CreatePKCS10(dn, "1.3.6.1.5.5.7.3.2");
    checkWindowsRequest(request);
    return false;
}

/**
 * Create a certificate from a "keygen"-capable browser. Such browsers include
 * Mozilla, Opera, Webkit-based browsers (Safari, Google Chrome).
 *
 * Note that currently Safari on iPhone does not support the keygen tag, but it
 * is, according to Apple, not impossible that it will be implemented in the
 * future
 */
function createKeygenTag(dn, keysize)
{
      var keygen_tag = "<form method=\"post\" action=\"process_csr.php\">" +
			"<table>" +
			"<tr>" +
			"<td width=\"20%\">" +
			"<keygen name=\"browserRequest\" keytype=\"RSA\"></kegygen>" +
			"</td><td>" +
			"<p class=\"info\">We strongly recommend to choose a key with keysize " +
			"<b>" + keysize +
			" bits.</b> Please check which keysizes correspond to which \"grades\" in your browser!</p>" +
			"</td>" +
			"</tr><tr><td>" +
			"<input type=\"submit\" value=\"Send\" />" +
			"</td><td><br /><p class=\"info\">Please press the send button only <b>once</b>.</p></td>" +
			"</tr></table></form>";
    document.getElementById("info_view").innerHTML = keygen_tag;
    document.getElementById("reqForm").style.display = "none";
    return false;
}

/**
 * Create a request from the respective DN
 * Firefox currently only supports doing that with the CRMF and Netscape SPKAC
 * protocols. Still better to use RFC-specified CRMF protocol.
 */
function createRequest(dn, keysize)
{
	if (!confirm("Really request and sign a new X.509 certificate for\nDN " + dn + "?")) {
		return false;
	}

	if (navigator.userAgent.indexOf("MSIE") > -1) {	/* Internet explorer */
		if (navigator.userAgent.indexOf("Windows NT 5.") == -1) { /* Windows Vista and later */
			return createIEVistaRequest(dn, keysize);
		} else {
			return createIEXPRequest(dn, keysize);
		}
	} else if ((navigator.userAgent.indexOf("Opera") > -1) ||
		  (navigator.userAgent.indexOf("AppleWebKit") > -1) ||
		  (navigator.userAgent.indexOf("Firefox") > -1)) {
		      createKeygenTag(dn, keysize);
		      return false;
	} else {
		 alert("Your browser is currently not supported.\nSupported browsers\nFirefox, Mozilla\n" +
			"Internet Explorer (XP, Vista, Windows 7)\n" +
			"Opera, Safari\n");
		return false;
	}
}

function installIEVistaCertificate()
{
    try {
	var classFactory = new ActiveXObject("X509Enrollment.CX509EnrollmentWebClassFactory");
	var objEnroll = classFactory.CreateObject("X509Enrollment.CX509Enrollment");
	objEnroll.Initialize(1) /* the request is for a user */
	/*
	 * 0x4 in the first parameter: allow untrusted root
	 * 0x1 in the last paremeter: certificate is base-64 encoded
	 */
	objEnroll.InstallResponse(4, g_ccc, 1, "");
    } catch (e) {
	var message="Hit the following problem when trying to install the cert: " + e.description
	+ "\nDid you generate the request with exactly that browser?";
	alert(message);
    }
}

function installIEXPCertificate()
{
    document.writeln("<OBJECT id=\"XEnroll\"\n" +
	    "classid=\"clsid:127698e4-e730-4e5c-a2b1-21490a70c8a1\"" +
	    "codebase=\"xenroll.dll\"></OBJECT>");
    try {
	XEnroll.acceptPKCS7(g_ccc);
    } catch (e) {
	alert("Hit an exception when installing.\nDid you generate the certificate request with exactly" +
		" this browser?");
    }
}

/**
 * Import the certificate
 * Note that this expects the certificate to be in a format already known to
 * the browser, like for instance JavaScript
 */
function installCertificate()
{

	if (navigator.userAgent.indexOf("MSIE") > -1) {	/* Internet explorer */
		if (navigator.userAgent.indexOf("Windows NT 5.") == -1) { /* Windows Vista and later */
			installIEVistaCertificate();
		} else {
			installIEXPCertificate();
		}
	} else {
		alert("Your browser is currently not supported.\nSupported browsers:\nFirefox/Mozilla" +
			"IE (XP, Vista, Windows 7)\n" +
			"Safari, Opera");
		return false;
	}

}
