<fieldset>
  <legend>Add new Subscriber</legend>
  <form action="" method="post">
    <input type="hidden" name="subscriber" value="add" />

    <table width="90%">
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    This is where you add new subscribers. Every element is
	    given a thorough explanation. If in doubt, consult other
	    NREN-administrators near you or operational support.
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>


      {* ------------------------------------------------------ *}
      {* Attribute Name (db_name)				*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr>
	<td colspan="2">
	  <h3>Attribute Name</h3>
	</td>
      </tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    The attribute-name of the subscriber, is the name the way it is
	    exported from the IdP.
	  </p>
	  <p class="info">
	    Normally, this one one of the following attributes:
	  </p>
	  <a href="http://rnd.feide.no/attribute/edupersonorgdn">
	    eduPersonOrgDN</a><br />
	  <a href="http://rnd.feide.no/content/schachomeorganization">
	    schacHomeOrganization</a><br />
	  <br />
	  <p class="info">
	    The important element to remember is that this must be
	    the <b>unique</b> identifier for the subscriber organization
	    exported by the IdP.
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td>
	  <font color="gray"><i>Attribute-name:</i></font>
	</td>
	<td>
	  <input type="text" name="db_name" size="40" />
	</td>
      </tr>
      {if isset($foundUniqueName)}
		<tr><td></td><td style="font-size: 0.8em; font-style: italic">e.g.: {$foundUniqueName|escape}</td></tr>
	{/if}
	  <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>


      {* ------------------------------------------------------ *}
      {* Org-Name						*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>DN Organization-Name</h3></td></tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    The Organization-Name is the name that will appear in the certificate for
	    the end entities (the users). I.e. it is the string appearing in the /O=...
	    section of the certificate. This name is subject to certain
	    restrictions pertaining length, encoding etc.
	  </p>
	  {if $confusa_grid_restrictions}
	  <br />
	  <p class="info">
	    Currently, Confusa is placed in "Grid-mode". This means that the
	    name for the subscriber is limited to ASCII and a maximum of
	    62 characters.
	  </p>
	  {/if}
	</td>
	</tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td><font color="gray"><i>/O=</i></font></td>

	{if $confusa_grid_restrictions}
	<td><input maxlength="62" type="text" name="dn_name" size="40"/></td>
	{else}
	<td><input type="text" name="dn_name" size="40"/></td>
	{/if}
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>


      {* ------------------------------------------------------ *}
      {* Contact information for subscriber			*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>Contact Information for subscriber</h3></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    This information is used by both the NREN-staff to contact the
	    subscribers in case of an emergency, but also by confusa itself
	    when revoking certificates, adding robot-certificates etc.
	  </p>
	  <p class="info">
	    It is important that the contact-information is as up-to-date as
	    possible.
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td><font color="gray"><i>Contact email:</i></font></td>
	<td><input type="text" name="subscr_email" size="40"/></td>
      </tr>
      <tr><td></td><td>
      <span style="font-size: 0.8em; font-style: italic">
	The "official" contact e-mail.
      </span></td>
      <tr>
	<td><font color="gray"><i>Contact phone:</i></font></td>
	<td><input type="text" name="subscr_phone" size="40" /></td>
      </tr>
      <tr><td></td><td>
      <span style="font-size: 0.8em; font-style: italic">
	The "official" subscriber phone.
      </span>
      </td>
      </tr>
      <tr>
	<td><font color="gray"><i>Responsible Person:</i></font></td>
	<td><input type="text" name="subscr_responsible_name" size="40" />
      </tr>
      <tr>
      <td></td>
      <td style="font-size: 0.8em; font-style: italic">
	Technical contact - not end user support
      </td>
      </tr>
      <tr>
	<td><font color="gray"><i>Responsible's email:</i></font></td>
	<td><input type="text" name="subscr_responsible_email" size="40" /></td>
      </tr>
      <tr>
      <td></td><td>
      <span style="font-size: 0.8em; font-style: italic">
        Technical contact mail
      </span>
      </td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>



      {* ------------------------------------------------------ *}
      {* Helpdesk information					*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>HelpDesk contact information</h3></td></tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    Whenever an end-user needs help, the user should be
	    directed to the helpdesk run by the subscriber. Operational
	    support or NREN-support should <b>not</b> be required to
	    handle large number of issues that can (and should) be
	    resolved by local IT-support staff.
	  </p>
	</td>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr>
	<td><font color="gray"><i>HelpDesk URL:</i></font></td>
	<td><input type="text" name="subscr_help_url" size="40"/></td>
      </tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr>
	<td><font color="gray"><i>HelpDesk Email:</i></font></td>
	<td><input type="text" name="subscr_help_email" size="40" /></td>
      </tr>


      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></td><td></td></tr>

      {* ------------------------------------------------------ *}
      {* Comment						*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>Arbitrary comment</h3></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    In case a general comment concerning the subscriber is needed, you
	    can add this here. This comment will only be available through
	    this page, so what you write here, will neither be exposed to
	    subscribers, nor to end entities.
	  </p>
	</td>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td colspan="2">
	  <textarea name="subscr_comment" rows="10" cols="60"></textarea>
	  </td>
      </tr>

      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>

      {* ------------------------------------------------------ *}
      {* Subscriber state					*}
      {* ------------------------------------------------------ *}
      <tr><td colspan="2"><h3>Subscriber State</h3></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    The state describes how a subscriber is treated. At the moment you
	    have 3 different states in Confusa
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2">
	  <dl style="padding-bottom: 1em">
	    <dt><i><u>Subscribed:</u></i></dt>
	    <dd class="info">
		When a subscriber is in this state, the contract between
		the NREN and subscriber is signed, the subscriber has
		agreed to follow the requirements placed upon it in the
		CP/CPS document etc. This state indicates that the
		subscriber is ready to use Confusa.
	    </dd>
	    <dt><i><u>Unsubscribed:</u></i></dt>
	    <dd class="info">
		The subscriber has been added to the register of
		Subscriber, but has not yet signed the contractual
		agreements and can therefore not be elevated to state
		"Subscribed".
	    </dd>
	    <dt><i><u>Suspended:</u></i></dt>
	    <dd>
		Due to violations of the terms in the contracts, all
		activity for the subscriber has been ceased. No
		certificates will be removed, but new will not be issued
		for the users.Once the issues have been resolved, state
		can be changed back to "Subscribed".
		</dd>

	  </dl>
	</td>
      </tr>
      <tr>
	<td><font color="gray"><i>Subscriber state:</i></font></td>
	<td>{html_options output=$org_states values=$org_states selected=$subscriber->getState() name=state}</td>
      </tr>

      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr><td colspan="2"><hr class="table"/><br /></td></tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    Before you continue, you should read through the values set to make
	    sure this is correct. If you are satisifed, you can continue to add
	    the subscriber.
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></div></td><td></td></tr>
      <tr>
	<td><input type="reset" value="Clear the form" /></td>
	<td><input type="submit" value="Add the subscriber" /></td>
      </tr>
    </table>
  </form>
  <br />
</fieldset>
