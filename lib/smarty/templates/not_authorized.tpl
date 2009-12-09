
<h2 class="unauthz">You are not authorized</h2>

<p class="unauthz">
  From your current user-credentials, we cannot confirm that you hold
  the required privileges to perform this action. You have therefore
  been denied access to this resource.
</p>
<br />
<p class="unauthz">
  If this is wrong, please contact your local support-staff and ask
  them to resolve the problem.
</p>
<br />
<p class="unauthz">
  <table>
    <tr>
      <td width="20px"></td>
      <td>Web:</td>
      <td width="30px"></td>
      <td><a href="{$subscriber->getHelpURL()}">{$subscriber->getHelpURL()}</a></td>
    </tr>
    <tr>
      <td></td>
      <td>Email:</td>
      <td></td>
      <td>
	<a href="mailto:{$subscriber->getHelpEmail()}">{$subscriber->getHelpEmail()}</a>
      </td>
    </tr>
  </table>
</p>
