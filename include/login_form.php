<!-- loginform for slcsweb-auth -->
<FORM ACTION="index.php" METHOD="POST">
<TABLE class="login">
  <TR>
    <TD>
      <LABEL for="passwd">SMS-Password:</LABEL>
    </TD>
    <TD>
      <INPUT type="password" name="passwd" id="passwd" width="50">
    </TD>
  </TR>
  <TR>
    <TD><LABEL for="new_pw">Send new</LABEL></TD>
    <TD><INPUT type="checkbox" name="new_pw" value="New Password">
  </TR>
  <TR>
    <TD>
      <INPUT NAME="start_login" VALUE="yes" TYPE="hidden">
      <input type="submit" value="login">
      </TD>
  </TR>
</TABLE>



