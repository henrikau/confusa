<fieldset>
  <legend>Add new Subscriber</legend>
  <form action="" method="post">
    <input type="hidden" name="subscriber" value="add" />

    <table width="90%">
      <tr>
	<td colspan="2">
	  <p class="info">
	    This is where you add new subscribers. Every element is
	    given a thorough explanation. If in doubt, consult other
	    NREN-administrators near you or operational support.
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></td><td></td></tr>

      <tr><td colspan="2"><hr /></td></tr>
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
	  <br />
	  <p class="info">
	    Normally, this one one of the following attributes:
	  </p>
	  <ul>
	    <li>
	      <a href="http://rnd.feide.no/attribute/edupersonorgdn">
	      eduPersonOrgDN</a>
	    </li>
	    <li>
	      <a href="http://rnd.feide.no/content/schachomeorganization">
	      schachHomeOrganization</a>
	    </li>
	  </ul>
	  <br />
	  <p class="info">
	    The important element to remember is that this must be
	    the <b>unique</b> identifier for the subscriber organization
	    exported by the IdP.
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr>
	<td>
	  <font color="gray"><i>Attribute-name:</i></font>
	</td>
	<td>
	  <input type="text" name="db_name" />
	</td>
      </tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2"><hr /></td></tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2"><h3>DN-Name</h3></td></tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    The DN-Name is the name that will appear in the certificate for
	    the end entities (the users). This name is subject to certain
	    restrictions pertaining length, encoding etc.
	  </p>
	  {if $confusa_grid_restrictions}
	  <br />
	  <p class="info">
	    Currently, Confusa is placed in "Grid-mode". This means that the
	    name for the subscriber is limited to 7-bit asci and a maximum of
	    64 characters.
	  </p>
	  {/if}
	</td>
	</tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr>
	<td><font color="gray"><i>DN-Name:</i></font></td>
	<td><input type="text" name="dn_name" /></td>
      </tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2"><hr /></td></tr>
      <tr><td><div class="spacer"></td><td></td></tr>


      <tr><td colspan="2"><h3>Contact Information for subscriber</h3></td></tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    This information is used by both the NREN-staff to contact the
	    subscribers in case of an emergency, but also by confusa itself
	    when revoking certificates, adding robot-certificates etc.
	  </p>
	  <br />
	  <p class="info">
	    It is important that the contact-information is as updated as
	    possible. Note that at the moment, the subscriber cannot alter
	    this information.
	  </p>
	</td>
      </tr>
      <tr>
	<td><font color="gray"><i>Contact email:</i></font></td>
	<td><input type="text" name="subscr_email" /></td>
      </tr>
      <tr>
	<td><font color="gray"><i>Contact phone:</i></font></td>
	<td><input type="text" name="subscr_phone" /></td>
      </tr>
      <tr>
	<td><font color="gray"><i>Responsible Person:</i></font></td>
	<td><input type="text" name="subscr_responsible_name"/>
      </tr>
      <tr>
	<td><font color="gray"><i>Responsible's email:</i></font></td>
	<td><input type="text" name="subscr_responsible_name" /></td>
      </tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2"><hr /></td></tr>
      <tr><td><div class="spacer"></td><td></td></tr>


      <tr><td colspan="2"><h3>Arbitrary comment</h3></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    In case a general comment concerning the subscriber is needed, you
	    can add this here. This comment will only be available through
	    this page, so what you write here, will never be exposed to
	    neither subscribers, nor end entities.
	  </p>
	</td>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr>
	<td colspan="2">
	  <textarea name="subscr_comment" rows="10" cols="70"></textarea>
	  </td>
      </tr>

      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2"><hr /></td></tr>
      <tr><td><div class="spacer"></td><td></td></tr>

      <tr><td colspan="2"><h3>Subscriber State</h3></td></tr>
      <tr><td colspan="2">
	  <p class="info">
	    The state describes how a subscriber is treated. At the moment you
	    have 3 different states in confusa
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2">
	  <dl>
	    <dt><i><u>Subscribed:</u></i></dt>
	    <dd>
	      When a subscriber is subscribed, the contract between the NREN
	      and subscriber is signed, the subscriber has agreed to follow the
	      requirements placed upon it in the CP/CPS document etc. This state
	      indicates that the subscriber is ready to use Confusa.
	    </dd>
	    <br />
	    <dt><i><u>Unsubscribed:</u></i></dt>
	    <dd>
	      Added to confusa, but not yet formally approved to start using
	      the service
	    </dd>
	    <br />
	    <dt><i><u>Suspended:</u></i></dt>
	    <dd>
	      not able to create new certs, no existing will be revoked.
	    </dd>
	    <br />

	  </dl>
	</td>
      </tr>
      <tr>
	<td><font color="gray"><i>Subscriber state:</i></font></td>
	<td>{$nren->createSelectBox('', null, 'state')}</td>
      </tr>

      <tr><td><div class="spacer"></td><td></td></tr>
      <tr><td colspan="2"><hr /></td></tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr>
	<td colspan="2">
	  <p class="info">
	    Before you continue, you should read through the values set to make
	    sure this is correct. If you are satisifed, you can continue to add
	    the subscriber.
	  </p>
	</td>
      </tr>
      <tr><td><div class="spacer"></td><td></td></tr>
      <tr>
	<td><input type="reset" value="Clear the form" /></td>
	<td><input type="submit" value="Add the subscriber" /></td>
      </tr>
    </table>
  </form>
  <br />
</fieldset>
