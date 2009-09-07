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

/**
 * Create a request from the respective DN
 * Firefox currently only supports doing that with the CRMF and Netscape SPKAC
 * protocols. Still better to use RFC-specified CRMF protocol.
 */
function createRequest(dn, keysize)
{

	if (window.crypto) {

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

	} else {
		alert("Your browser is currently not supported.\nSupported browsers: Firefox/Mozilla");
		return false;
	}
}

/**
 * Import the certificate
 * Note that this expects the certificate to be in a format already known to
 * the browser, like for instance JavaScript
 */
function installCertificate()
{
	try {
		var error = window.crypto.importUserCertificates("Confusa certificate", g_ccc, true);
	} catch (e) {
		alert("Installation FAILED!\nNote that you can only install " +
		"certificates for browser-generated requests! ");
	}
}
