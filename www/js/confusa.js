/* globally define the XMLHttpRequest object, if not present.
 * Mostly this is necessary for the folks still surfing with IE 6. */
if (typeof(XMLHttpRequest) == "undefined") {
	XMLHttpRequest = function() {
		/* define XMLHttpRequest for IE versions < 7 */
		try { return new ActiveXObject("Msxml2.XMLHTTP.6.0"); }
		catch(e) {}
		try { return new ActiveXObject("Msxml2.XMLHTTP.3.0"); }
		catch(e) {}
		try { return new ActiveXObject("Msxml2.XMLHTTP"); }
		catch(e) {}
		try { return new ActiveXObject("Microsoft.XMLHTTP"); }
		catch(e) {}
	};
}

/**
 *  expand or collapse the current item. Requires DOM methods.
 */
function toggleExpand(doc, expandElem, collapseElem) {

	if (typeof collapseElem == 'undefined') {
		collapseElem = '-';
	}

	if (typeof expandElem == 'undefined') {
		expandElem = '+';
	}

	/* Check if the needed DOM functionality is available */
	if (document.getElementById) {
		var focus = doc.firstChild;
		focus = doc.firstChild.innerHTML?doc.firstChild:doc.firstChild.nextSibling;

		/* comparing HTML elements is not reliable, because JavaScript can strip closing tags
		 * like in /> to >, so they do not end up being regarded as equal any more.
		 * As an alternative, add an "expand" and "collapse" class attribute to them,
		 * which JavaScript can simply compare
		 */
		if (focus.innerHTML == expandElem ||
		    (focus.firstChild.nodeType === 1 &&
		    focus.firstChild.className == "expand")) {
			focus.innerHTML = collapseElem;
		} else {
			focus.innerHTML = expandElem;
		}

		focus = doc.parentNode.nextSibling.style?
			doc.parentNode.nextSibling:
			doc.parentNode.nextSibling.nextSibling;
		focus.style.display = focus.style.display=='block'?'none':'block';
	}

   else if(!document.getElementById) {
	   document.write('<style type="text/css"><!--\n'+
		  '.expcont{display:block;}\n'+
		  '//--></style>');
	}
}

/**
 * Install a processed certificate in Internet Explorer on Windows Vista
 * Requires ActiveX
 */
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
		var message="Hit the following problem when trying to install the cert: "
		+ e.description +
		"\n\nPlease check if the certificate is already installed in your keystore.";
		alert(message);
    }
}

/**
 * Install a processed certificate in Internet Explorer on Windows XP
 * Requires ActiveX.
 */
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
