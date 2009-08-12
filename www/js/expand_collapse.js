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

   else if(!document.getElementById) {
	   document.write('<style type="text/css"><!--\n'+
		  '.expcont{display:block;}\n'+
		  '//--></style>');
	}
}
