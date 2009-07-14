<?php
include_once('framework.php');
include_once('mdb2_wrapper.php');
include_once('db_query.php');
include_once('logger.php');
$org_states = array('subscribed', 'suspended', 'unsubscribed');
$fw = new Framework('admin');
$fw->force_login();
$fw->render_page();

/**
 * Serves as a dispatcher function that calls the right admin-functionality
 * based on the authenticated person and the received query string.
 *
 * @param $person Object representing the authenticated user
 */
function admin($person)
{
  try {
    if ($person->is_nren_admin()) {
        $nren = get_nren($person);
        if (isset($_GET['subscribe'])) {
            switch($_GET['subscribe']) {
            case 'manage':
                show_subscriptions_mask($nren);
                break;

            case 'edit':
                edit_subscriptions($_POST['org_name'],
                                   $_POST['org_state'],
                                   $nren);
                show_subscriptions_mask($nren);
                break;

            case 'add':
                add_subscription($nren,
                                 $_POST['org_name']);
                show_subscriptions_mask($nren);
                break;

            case 'delete':
                delete_subscription($_POST['org_name'], $nren);
                show_subscriptions_mask($nren);
                break;

            default:
                echo "Unknown operation<br />\n";
                break;
            }
        }
      }
    } catch (ConfusaGenException $e) {
      echo $e->getMessage() . "<br />\n";
    }
}

/**
 * Add an organization subscription to the service. An associated
 * organization (usually an identity-vetting institution) can have
 * one of these three states: subscribed, suspended, unsubscribed and
 * belongs to an NREN
 *
 * @param $org_state The subscription state of the organization
 * @param $nren The NREN to which the organization belongs
 * @param $org_name The name of the organization to be added
 * */
function add_subscription($nren, $org_name)
{
    $nren = sanitize($nren);
    $org_name = sanitize($org_name);

    $query = "SELECT nren_id FROM nrens WHERE name=?";
    $res = MDB2Wrapper::execute($query, array('text'),$nren);

    if (count($res) != 1) {
    throw new DBQueryException("Could not get the nren-ID for NREN " .
                               "$nren"
    );
    }

    $nren_id = $res[0]['nren_id'];

    $stmt = "INSERT INTO organizations(name,nren_id,org_state) VALUES(?,?,?)";

    MDB2Wrapper::update($stmt, array('text','text','text'),
                      array($org_name, $nren_id, 'subscribed')
    );

    Logger::log_event(LOG_NOTICE, "Added the subscription $org_name with " .
                    "NREN $nren and state $org_state as a subscriber "
    );
}

/**
 * Edit the subscription status or the associated subaccount of the
 * organization passed..
 * Check the received organization state or subaccount-name carefully
 * for their feasabiltiy before performing an update (i.e. check if they
 * are already in the database without passing them to SQL).
 *
 * @param $org The organization whose subscription status is to be changed
 * @param $new_org_state The new subscription state of the organization
 * @param $new_nren_name The name of the NREN to which the organization belongs
 */
function edit_subscriptions($org, $new_org_state, $new_nren_name)
{
    global $org_states;

    /* check if we have to organization in the DB. Thus we make sure
     * the sent post-data is sane and not malicious
     * Also check all other input values for sanity.*/
    if (array_search($org, get_organization_names()) === FALSE) {
        throw new ConfusaGenException("The organization $org you were about to edit " .
                                      "is unknown!");
    } else if (array_search($new_org_state, $org_states) === FALSE) {
        throw new ConfusaGenException("Tried to update organization state " .
                                      "to an unknown value!"
        );
    } else if (array_search($new_nren_name, get_nren_names()) === FALSE) {
        throw new ConfusaGenException("Tried to update NREN name " .
                                      "to an unknown value!"
        );
    } else {

      /* get the right nren-id first. subselects are expensive */
      $res = MDB2Wrapper::execute("SELECT nren_id FROM nrens " .
                                  "WHERE name = ?",
                                  array('text'),
                                  $new_nren_name
      );

      if (count($res) != 1) {
        throw new DBQueryException("Could not retrieve a valid nren_id for " .
                                   "login name $new_nren_name"
        );
      }

      $nren_id = $res[0]['nren_id'];
      $stmt = "UPDATE organizations SET org_state = ?, nren_id = ? " .
              "WHERE name = ?";
      MDB2Wrapper::update($stmt, array('text','text', 'text'),
                                 array($new_org_state, $nren_id ,$org)
      );

      Logger::log_event(LOG_NOTICE, "Changed subscription $org in NREN " .
                                    "$new_nren_name to state " .
                                    "$new_org_state.\n"
      );
    }
}

/**
 * Delete a registered (identity-vetting) institution iff it "belongs to" NREN
 * $nren
 *
 * @param $org Organization to be deleted
 * @param $nren NREN to which the organization must belong
 */
function delete_subscription($org, $nren)
{
  $org = sanitize($org);
  /* double check if the deletion of that organization by the NREN is allowed */
  $subselect = "SELECT nren_id FROM nrens WHERE name=?";
  $select = "DELETE FROM organizations WHERE name=? AND nren_id=($subselect)";

  /* let's hope that this going to be succesful */
  MDB2Wrapper::execute($select, array('text', 'text'), array($org, $nren));
}

/**
 * Show a mask with all the institutions subscribed to the service, but only
 * if the are managed by NREN $nren
 *
 * @param $nren string The NREN to which the institutions must belong in order
 * to show up
 */
function show_subscriptions_mask($nren)
{
    global $org_states;

    /*
     * mimic tables with <div> elements, because in legal html, forms
     * may not be interleaved with table elements, i.e. we can not
     * place a form within a whole table row.
     *
     * But for <div>s, there is no such restriction
     */
    $table="<div class=\"admin_table\">\n";
    $tr="<div class=\"admin_table_row\">\n";
    $td="<div class=\"admin_table_cell\">\n";
    $table_e = "</div>\n";
    $tr_e = "</div>\n";
    $td_e = "</div>\n";

    echo "Add a new subscribed institution:<br />";
    echo "<div class=\"spacer\"></div>";
    echo "<form action=\"?subscribe=add\" method=\"POST\">";
    echo "<input type=\"text\" name=\"org_name\" />";
    echo "<input type=\"submit\" value=\"Add\" />";
    echo "</form>";

    echo "<div class=\"spacer\"></div>";

    /* select existing institutions and the associated NRENs */
    $query = "SELECT o.name, o.org_state " .
             "FROM organizations o, nrens n WHERE o.nren_id = n.nren_id " .
             "AND n.name = ?";
    echo $table;
    echo "$tr$td$td_e$td<b>Institution</b>$td_e$td<b>State</b>$td_e$tr_e";

    $res = MDB2Wrapper::execute($query, array('text'), array($nren));

    if (count($res) >= 1) {
      for ($i = 0; $i < count($res); $i++) {
            $cur_name = $res[$i]['name'];
            echo $tr;
            echo $td;
            echo "<form action=\"?subscribe=delete\" method=\"POST\">\n";
            echo "<input type=\"hidden\" name=\"org_name\" value=\"$cur_name\" />\n";
            echo "<input type=\"image\" name=\"delete\" " .
                 "onclick=\"return confirm('Delete entry?')\" " .
                 "value=\"delete\" src=\"graphics/delete.png\" alt=\"delete\" />\n";
            echo "</form>\n";
            echo $td_e;
            echo "$td<form action=\"?subscribe=edit\" method=\"POST\">\n";
            echo $cur_name . $td_e;
            echo $td;
            display_selectbox($res[$i]['org_state'],
                              $org_states,
                              'org_state'
            );
            echo $td_e;
            echo "$td<input type=\"hidden\" name=\"org_name\" value=\"$cur_name\" />";
            echo "<input type=\"submit\" name=\"Update\" class=\"button\" value=\"Update\" />";
            echo "</form>\n";
            echo $td_e;
            echo $tr_e;
      }
    }

    echo $table_e;
}

/**
 * Get the NREN which is responsible for the managed person.
 * This is retrieved via a JOIN over the institution to which the person
 * belongs
 *
 * @param $person The person for which the respective NREN is retrieved
 */
function get_nren($person)
{

  /* TODO: maybe make the NREN an attribute of person, since person
   * is actually the object which should store information like that
   */
  if(isset($_SESSION['nren'])) {
    return $_SESSION['nren'];
  }

  $organization = $person->get_orgname();
  $query = "SELECT n.name FROM nrens n, organizations o " .
           "WHERE o.nren_id = n.nren_id AND o.name = ?";
  $res = MDB2Wrapper::execute($query, array('text'),array($organization));

  if (count($res) == 1) {
    $_SESSION['nren'] = $res[0]['name'];
    return $res[0]['name'];
  } else {
    throw new ConfusaGenException("Can not map your identitity to an NREN!");
  }
}

/**
 * display a select-box with the elements in choices as the alternative
 * highlight the element passed as 'active'
 *
 * @param $active The element that is going to be highlighted
 * @param $choices The whole list of choices
 * @param $sel_name The name of the selection form that is transmitted with POST
 */
function display_selectbox($active, $choices, $sel_name)
{
    echo "<select name=\"$sel_name\">\n";

    foreach($choices as $element) {
        if ($element == $active) {
            echo "<option value=\"$element\" selected=\"selected\">" . $element . "</option>\n";
        } else {
            echo "<option value=\"$element\">" . $element . "</option>\n";
        }
    }

    echo "</select>\n";
}

/**
 * Return a list of all the nren names.
 */
function get_nren_names()
{
    $nren_names=array();
    $query = "SELECT name FROM nrens";
    $res = MDB2Wrapper::execute($query, NULL, NULL);

    foreach($res as $row) {
        $nren_names[] = $row['name'];
    }

    return $nren_names;
}

/**
 * Return a list of all signed-up institutions.
 */
function get_organization_names()
{
  $org_names=array();
  $query = "SELECT name FROM organizations";
  $res = MDB2Wrapper::execute($query, NULL, NULL);

  foreach($res as $row) {
    $org_names[] = $row['name'];
  }

  return $org_names;
}

/**
 * Remove anything that could be dangerous from user input.
 * Our organization names should contain only [a-z][0-9], like the nren
 * names, like the states. So all inputs can be limited to [a-z][0-9]
 *
 * TODO: This function should be accessible for all forms taking data
 */
function sanitize($input)
{
  if (is_array($input)) {
    foreach($input as $var=>$val) {
      $output[$var] = sanitize($val);
    }
  }

  $output = preg_replace('/[^a-z0-9_ ]+/i','',$input);
  return $output;
}

?>
