var timer="";

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
