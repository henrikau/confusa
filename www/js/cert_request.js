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

	info_view.innerHTML = message;
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
		crmf=crypto.generateCRMFRequest(dn, "regToken", "authenticator", null, "checkCRMF();" , keysize, null, "rsa-dual-use");

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
	var error = window.crypto.importUserCertificates("Confusa certificate", g_ccc, true);

	/* there seems to be a bug in the crypto-API causing Firefox to not return an error
	 * code
	 *
	 * TODO: figure out some other way to find out if the import worked or failed
	 * */
	if (error != "") {
		alert("The following error occured when trying to install the certificate: " +
				error);
	}
}
