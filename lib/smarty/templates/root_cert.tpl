<H3>Root CA</H3>

This is the Certificate we use for signing the CSRs we receive.
If you want the certificate, it can be downloaded from <A HREF="{$ca_file}">here</A><BR>
</P>

<P>
Or, if you want to download it directly, press here:
<FORM METHOD="GET" action="root_cert.php">
<INPUT TYPE="hidden" name="send_file" VALUE="">
</FORM>

<HR />
<BR />
<PRE>
{$ca_dump}
</PRE>