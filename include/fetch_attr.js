<script type="text/javascript">
    <!-- Ask the stylist for the value of the attribute key	-->
    <!-- currently selected in 'selectElement' with a polite 	-->
    <!-- asynchronous GET message					-->
    <!--  -->
    <!-- @param	selectElement DOM-Node	The <select> containing	-->
    <!--					the selected attribute	-->
    <!--					key			-->
    <!-- @param	targetElementID string	The ID of the element	-->
    <!--					where the value should	-->
    <!--					be written to		-->
    <!-- @param	errMsg string: A string that will be displayed if the -->
    <!--						attribute value can not be fetched -->
    <!-- @param anticsrf String: A string contaning a unique token to prevent CSRF -->
    <!-- @return	void						-->
    function fetchAttributeValue(selectElement, targetElementID,  errMsg, anticsrf)
{
    var req   = new XMLHttpRequest();
    var field = document.getElementById(targetElementID);
    var path  = "?attr_value=" + selectElement.value + "&" + anticsrf;

    req.open("GET", path, true);

    req.send(null);
    req.onreadystatechange = function() {
		if (req.readyState == 4 /*complete*/) {
		    if (req.status == 200) {
			if (req.responseText.length > 10 &&
			    req.responseText.substr(0,10) == "attribute=") {
			    var text = req.responseText.substr(10,req.responseText.length);
				field.innerHTML = text;
				field.title = text;
    		}
    	    } else {
    		field.innerHTML = errMsg;
    	    }
    	}
    }}

</script>
