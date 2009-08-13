<?php
header("HTTP/1.1 404 Not Found");
function not_found($not_found)
{
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL <?php echo $not_found ?> was not found on this server.</p>
<hr>
</body></html>

<?php
}
?>
