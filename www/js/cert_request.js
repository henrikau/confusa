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

function installIEVistaCertificate()
{
    try {
	/* X509Enrollment.CX509EnrollmentWebClassFactory */
	document.writeln("<object classid=\"clsid:884e2049-217d-11da-b2a4-000e7bbb2b09\"" +
	"id=\"classFactory\" height=\"0\" width=\"0\" ></object>");
	var objEnroll = classFactory.CreateObject("X509Enrollment.CX509Enrollment");
	objEnroll.Initialize(1) /* the request is for a user */
	/*
	 * 0x4 in the first parameter: allow untrusted root
	 * 0x1 in the last paremeter: certificate is base-64 encoded
	 */
	objEnroll.InstallResponse(4, g_ccc, 1, "");
    } catch (e) {
	var message="Hit the following problem when trying to install the cert: " + e.description;
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
