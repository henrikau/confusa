// create a CRMF request and a respective key

var crmf="";
var timer="";

/**
 * Submit the form request, but decorate the POST object with the certificate
 * request in CRMF format first
 */
function checkCRMF() {
    with (document.reqForm) {
        browserRequest.value = crmf.request;
        submit();
    }
}

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
    /* use "RSA-full" as the key algorithm */
    objPrivateKey.ProviderType = "1";
    objRequest.InitializeFromPrivateKey(1, objPrivateKey, "");
    objDN.Encode(dn, 0);
    objRequest.Subject = objDN;
    objEnroll.InitializeFromRequest(objRequest);
    request = objEnroll.CreateRequest(1);
    checkWindowsRequest(request);
    return false;
}

function createMozillaRequest(dn, keysize)
{
    if (!confirm("Really request and sign a new X.509 certificate for\nDN " + dn + "?")) {
	return false;
    }

    try {
	crmf=crypto.generateCRMFRequest(dn, "regToken", "authenticator", null, "checkCRMF();" , keysize, null, "rsa-dual-use");
    } catch (e) {
	alert("Could not generate a new certificate signing request.\nProblem is " + e);
    }

    if (crmf.request.substring(0,6) == "error:") {
	    alert("Error occured: " + crmf);
	    return false;
    }

    return false;
}

/**
 * Create a request from the respective DN
 * Firefox currently only supports doing that with the CRMF and Netscape SPKAC
 * protocols. Still better to use RFC-specified CRMF protocol.
 */
function createRequest(dn, keysize)
{
	/* Firefox, Mozilla */
	if (window.crypto) {
		return createMozillaRequest(dn, keysize);

	} else if (navigator.userAgent.indexOf("MSIE") > -1) {	/* Internet explorer */
		if (navigator.userAgent.indexOf("Windows NT 5.1") == -1) { /* Windows Vista and later */
			return createIEVistaRequest(dn, keysize);
		}
	} else {
		alert("Your browser is currently not supported.\nSupported browsers: Firefox/Mozilla");
		return false;
	}
}

function installIEVistaCertificate()
{
    var classFactory = new ActiveXObject("X509Enrollment.CX509EnrollmentWebClassFactory");
    var objEnroll = classFactory.CreateObject("X509Enrollment.CX509Enrollment");
    objEnroll.Initialize(1) /* the request is for a user */
    /*
     * 0x4 in the first parameter: allow untrusted root
     * 0x1 in the last paremeter: certificate is base-64 encoded
     */
    objEnroll.InstallResponse(4, g_ccc, 1, "");
}

function installMozillaCertificate()
{
    try {
	var error = window.crypto.importUserCertificates("Confusa certificate", g_ccc, true);
    } catch (e) {
	    alert("Installation FAILED!\nNote that you can only install " +
	    "certificates for browser-generated requests! ");
    }
}

/**
 * Import the certificate
 * Note that this expects the certificate to be in a format already known to
 * the browser, like for instance JavaScript
 */
function installCertificate()
{

	/* Firefox, Mozilla */
	if (window.crypto) {
	    installMozillaCertificate();
	} else if (navigator.userAgent.indexOf("MSIE") > -1) {	/* Internet explorer */
		if (navigator.userAgent.indexOf("Windows NT 5.1") == -1) { /* Windows Vista and later */
			installIEVistaCertificate();
		}
	} else {
		alert("Your browser is currently not supported.\nSupported browsers: Firefox/Mozilla");
		return false;
	}

}
