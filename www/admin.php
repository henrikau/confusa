<?php
include_once('framework.php');
include_once('mdb2_wrapper.php');
include_once('db_query.php');
include_once('logger.php');
$org_states = array('','subscribed', 'suspended', 'unsubscribed');
$fw = new Framework('admin');
$fw->force_login();
$fw->render_page();

/*
 * Direct the user to the respective operations she may perform
 * Currently the admin page can manage NREN subscriptions,
 * organization subscriptions and (Comodo) subaccounts.
 *
 * The post parameters that are passed are supposed to be sanitized in
 * the respective functions that take them
 */
function admin($person)
{
     /*
      * Authentication check is performed along along with the is_admin
      * check
      */
     if ($person->is_admin()) {

        if (isset($_GET['subscribe'])) {
            switch($_GET['subscribe']) {
              case 'manage':
                    show_subscriptions_mask();
                    break;
              case 'edit':
                    edit_subscriptions($_POST['org_name'],
                                       $_POST['org_state'],
                                       $_POST['nren_name']);
                    break;
              case 'add':
                    add_subscription($_POST['org_state'],
                                     $_POST['nren'],
                                     $_POST['org_name']);
                    break;
              case 'delete':
                    delete_subscription($_POST['org_name']);
                    break;
              default:
                    echo "Unknown operation!<br />\n";
                    break;
            }
          } else if (isset($_GET['account'])) {
            switch($_GET['account']) {
              case 'manage':
                  show_accounts_mask();
                  break;
              case 'add':
                  add_account($_POST['login_name'], $_POST['login_password']);
                  break;
              case 'delete':
                  delete_account($_POST['login_name']);
                  break;
              case 'edit':
                  edit_account($_POST['login_name'], $_POST['login_password']);
                  break;
              default:
                 echo "Unknown operation!<br />\n";
                 break;
              }
          } else if (isset($_GET['nren'])) {
            switch($_GET['nren']) {
              case 'manage':
                show_nrens_mask();
                break;
              case 'add':
                add_nren($_POST['nren_name'],
                         $_POST['login_name']);
                break;
              case 'delete':
                delete_nren($_POST['nren_name']);
                break;
              case 'edit':
                edit_nren($_POST['nren_name'],
                          $_POST['login_name']);
                break;
              default:
                echo "Unknown operation!<br />\n";
                break;
            }
         }
    }
}

/*
 * Show a mask with all the organizations currently subscribed to the
 * service. Show visual elements permitting the user to update, add and
 * delete such entries */
function show_subscriptions_mask()
{
    global $org_states;
    global $org_name_cache;
    $org_name_cache=array();

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

    if(Config::get_config('standalone')) {
        $query = "SELECT name, org_state FROM organizations";
    } else {
        /* select the organizations and the associated NRENs */
        $query = "SELECT o.name AS \"name\", o.org_state AS \"org_state\", n.name AS \"nren_name\" " .
                 "FROM organizations o LEFT JOIN " .
                 "nrens n ON o.nren_id = n.nren_id";
    }

    echo $table;
    echo "$tr$td$td_e$td<b>Organization</b>$td_e$td<b>State";
    echo "</b>$td_e$td<b>NREN</b>$td_e$td$td_e$tr_e";

    $res = MDB2Wrapper::execute($query, NULL, NULL);

    if (count($res) >= 1) {
        for ($i = 0; $i < count($res); $i++) {
            $cur_name = $res[$i]['name'];
            $org_name_cache[] = $cur_name;
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
            echo "$td_e$td";
            display_selectbox($res[$i]['nren_name'],
                              get_nren_names(),
                              'nren_name'
                              );

            echo $td_e;
            echo "$td<input type=\"hidden\" name=\"org_name\" value=\"$cur_name\" />";
            echo "<input type=\"submit\" name=\"Update\" class=\"button\" value=\"Update\" />";
            echo "</form>\n";
            echo $td_e;
            echo $tr_e;
        }
      }

        echo "<div class=\"spacer\"></div>";
        echo "$tr$td$td_e";
        echo "$td<form action=\"?subscribe=add\" method=\"POST\">";
        echo "<input type=\"text\" name=\"org_name\" />$td_e";
        echo $td;
        display_selectbox('',$org_states,'org_state');
        echo "$td_e$td";
        display_selectbox('', get_nren_names(), 'nren');
        echo $td_e;
        echo "$td<input type=\"submit\" value=\"Add new\" />";
        echo "</form>$td_e";
        echo $tr_e;
        echo $table_e;
}

/*
 * Edit the subscription status or the associated subaccount of the
 * organization passed..
 * Check the received organization state or subaccount-name carefully
 * for their feasabiltiy before performing an update (i.e. check if they
 * are already in the database without passing them to SQL).
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
        throw new ConfusaGenException("Tried to update login name " .
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

      Logger::log_event(LOG_INFO, "Changed organization $org to state " .
                                  "$new_org_state and nren_name $new_nren_name.\n");
    }
}

/*
 * Edit an existing account. This only refers to updating the encrypted
 * password entry. If the name of the account is to change as well, the
 * user is advised to just delete the account and re-create it
 */
function edit_account($login_name, $login_pw) {
    $login_name = sanitize($login_name);
    $login_pw = base64_encode($login_pw);

    $enckey = Config::get_config('capi_enc_pw');
    $size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
    $iv=mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);

    $cryptpw = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$enckey,$login_pw,
                              MCRYPT_MODE_CFB, $iv
                ));

    $query = "UPDATE account_map SET password=?,ivector=? " .
             "WHERE login_name=?";

    MDB2Wrapper::update($query,array('text','text','text'),
                        array($cryptpw, base64_encode($iv), $login_name));

    Logger::log_event(LOG_INFO, "Updated the password of account " .
                                "$login_name\n"
            );

}

/*
 * Edit an existing NREN. I.E. change the (Comodo) subaccount which
 * is associated with it
 */
function edit_nren($nren_name, $login_name) {
  $nren_name = sanitize($nren_name);
  $login_name = sanitize($login_name);

  $map_id_query = "SELECT map_id FROM account_map WHERE login_name=?";

  $res = MDB2Wrapper::execute($map_id_query, array('text'), $login_name);

  if (count($res) != 1) {
    throw new DBQueryException("Could not find the account map ID for " .
                              "login-name $login_name<br />\n"
              );
  }

  $map_id = $res[0]['map_id'];

  $update_query = "UPDATE nrens SET account_id=? WHERE name=?";
  MDB2Wrapper::update($update_query, array('text','text'),
                      array($map_id, $nren_name));

  Logger::log_event(LOG_INFO, "Updated NREN $nren_name to use new " .
                              "account $account_id\n");
}

/*
 * Show a mask permitting the user to add, update and delete remote-CA
 * subaccounts.
 */
function show_accounts_mask() {
  $table = "<div class=\"admin_table\">\n";
  $tr = "<div class=\"admin_table_row\">\n";
  $td = "<div class=\"admin_table_cell\">\n";
  $td_e="</div>\n";
  $tr_e = "</div>\n";
  $table_e = "</div>\n";

  $query = "SELECT login_name, password, ivector FROM account_map a ";


  echo $table;
  echo $tr;
  echo "$td$td_e$td<b>Login name</b>$td_e$td<b>Password</b>$td_e$td$td_e";
  echo $tr_e;
  $res = MDB2Wrapper::execute($query, NULL, NULL);

  if (count($res) >= 1) {

    foreach($res as $row) {
      $lname = $row['login_name'];
      echo $tr;
      echo $td;
      echo "<form action=\"?account=delete\" method=\"POST\">\n";
      echo "<input type=\"hidden\" name=\"login_name\" value=\"$lname\" />\n";
      echo "<input type=\"image\" name=\"delete\" " .
           "onclick=\"return confirm('Delete entry?')\" " .
           "value=\"delete\" src=\"graphics/delete.png\" alt=\"delete\" />\n";
      echo "</form>\n";
      echo $td_e;
      echo $td;
      echo "<form action=\"?account=edit\" method=\"POST\">\n";
      echo $lname;
      echo $td_e;
      echo $td;
      $pw =  trim(base64_decode(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,
                  Config::get_config('capi_enc_pw'), base64_decode($row['password']),
                  MCRYPT_MODE_CFB,base64_decode($row['ivector']))));

      echo "<input type=\"password\" name=\"login_password\" value=\"$pw\" />\n";
      echo $td_e;
      echo $td;
      echo "<input type=\"hidden\" name=\"login_name\" value=\"$lname\" />";
      echo "<input type=\"submit\" name=\"submit\" value=\"Update\" />";
      echo $td_e;
      echo "</form>";
      echo $tr_e;
    }
  }

  echo "<div class=\"spacer\"></div>";
  echo $tr;
  echo $td . $td_e;
  echo $td;
  echo "<form action=\"?account=add\" method=\"POST\">\n";
  echo "<input type=\"text\" name=\"login_name\" />\n";
  echo $td_e;
  echo $td;
  echo "<input type=\"password\" name=\"login_password\" />\n";
  echo $td_e;
  echo $td;
  echo "<input type=\"submit\" name=\"submit\" value=\"Add new\" />\n";
  echo $td_e;
  echo "</form>";
  echo $tr_e;
  echo $table_e;
}

/*
 * Show a mask permitting the user to add, delete and update subscribed
 * NRENs
 */
function show_nrens_mask()
{
  $table = "<div class=\"admin_table\">\n";
  $tr = "<div class=\"admin_table_row\">\n";
  $td = "<div class=\"admin_table_cell\">\n";
  $td_e="</div>\n";
  $tr_e = "</div>\n";
  $table_e = "</div>\n";

  $query = "SELECT n.name, a.login_name FROM nrens n LEFT JOIN account_map a" .
           " ON n.account_id = a.map_id";

  $res = MDB2Wrapper::execute($query, NULL, NULL);

  echo $table;
  echo $tr;
  echo "$td$td_e$td<b>NREN</b>$td_e$td<b>Account</b>$td_e$td$td_e";
  echo $tr_e;

  if (count($res) >= 1) {
    foreach($res as $row)  {
      $cur_nren = $row['name'];
      echo $tr;
      echo $td;
      echo "<form action=\"?nren=delete\" method=\"POST\">\n";
      echo "<input type=\"hidden\" name=\"nren_name\" value=\"$cur_nren\" />\n";
      echo "<input type=\"image\" name=\"delete\" " .
           "onclick=\"return confirm('Delete entry?')\" " .
           "value=\"delete\" src=\"graphics/delete.png\" alt=\"delete\" />\n";
      echo "</form>\n";
      echo $td_e;
      echo $td;
      echo "<form action=\"?nren=edit\" method=\"POST\">\n";
      echo $cur_nren;
      echo $td_e;
      echo $td;
      display_selectbox($row['login_name'], get_login_names(), 'login_name');
      echo $td_e;
      echo $td;
      echo "<input type=\"hidden\" name=\"nren_name\" value=\"$cur_nren\" />\n";
      echo "<input type=\"submit\" name=\"submit\" value=\"Update\" />\n";
      echo $td_e;
      echo "</form>\n";
      echo $tr_e;
    }
  }

  echo "<div class=\"spacer\"></div>";
  echo $tr;
  echo $td;
  echo $td_e;
  echo $td;
  echo "<form action=\"?nren=add\" method=\"POST\">\n";
  echo "<input type=\"text\" name=\"nren_name\" />\n";
  echo $td_e;
  echo $td;
  display_selectbox('',get_login_names(),'login_name');
  echo $td_e;
  echo $td;
  echo "<input type=\"submit\" name=\"add\" value=\"Add new\" />\n";
  echo $td_e;
  echo "</form>\n";
  echo $tr_e;
  echo $table_e;
}

/*
 * Add an organization subscription to the service. An associated
 * organization (usually an identity-vetting institution) can have
 * one of these three states: subscribed, suspended, unsubscribed and
 * belongs to an NREN */
function add_subscription($org_state, $nren, $org_name)
{
  $org_state = sanitize($org_state);
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
                      array($org_name, $nren_id, $org_state));

  Logger::log_event(LOG_INFO, "Added the organization $org_name with " .
                    "NREN $nren and state $org_state as a subscriber ");
}

/*
 * Add remote-CA subaccount information to the system.
 * The password will be stored in the DB encrypted and due to MySQL
 * encryption deficiencies it will be encrypted in the application layer,
 * i.e. here. The initialization vector IV for the used block mode is
 * stored along with the encrypted data, since it needs not be secret.
 * Thus the data can be easily decrypted again */
function add_account($login_name, $login_password)
{
  $login_name = sanitize($login_name);
  /* this will get encrypted before insertion to the database, so it
   * does not need to be sanitized. However, base64_encode allows the
   * user to include all kinds of special characters in the password */
  $login_password = base64_encode($login_password);
  $enckey = Config::get_config('capi_enc_pw');

  $size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
  $iv=mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);

  /*
   * It may seem overblown to base64-encode first the password and then
   * the encryption result again. But testing revealed that
   * insertion can fail sometimes if the encryption string is not b64-encoded.
   * It doesn't take very long to b64-encode and it makes inserting the
   * encrypted account into the DB safe.
   */
  $cryptpw = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$enckey,
                          $login_password, MCRYPT_MODE_CFB, $iv
             ));

  $ivector = base64_encode($iv);
  $query="INSERT INTO account_map(login_name, password, ivector)" .
         "VALUES(?, ?, ?)";

  MDB2Wrapper::update($query, array('text','text','text'),
                      array($login_name, $cryptpw, $ivector));

  Logger::log_event(LOG_INFO, "Inserted new account $login_name into " .
                              "account-map\n"
          );
}

/*
 * Add a NREN. A NREN usually has a name and an associated login-sub-
 * account.
 */
function add_nren($nren_name, $login_name) {
  $nren = sanitize($nren_name);
  $account = sanitize($login_name);

  $map_id_query = "SELECT map_id FROM account_map WHERE login_name=?";

  $res = MDB2Wrapper::execute($map_id_query, array('text'),
                              array($account));

  if (count($res) != 1) {
    throw new DB2QueryException("Could not retrieve the map_id of the " .
                                "selected account!<br />\n");
  }

  $map_id = $res[0]['map_id'];
  $query = "INSERT INTO nrens(name, account_id) VALUES(?, ?)";

  MDB2Wrapper::update($query, array('text','text'), array($nren, $map_id));

  Logger::log_event(LOG_INFO, "Added NREN with name $nren_name and login " .
                              "$login_name to the DB\n"
          );
}

/*
 * Delete a registered (identity-vetting) instituion
 */
function delete_subscription($org) {
  $org = sanitize($org);
  MDB2Wrapper::execute("DELETE FROM organizations WHERE name = ?",
                    array('text'),
                    $org
  );

  Logger::log_event(LOG_INFO, "Delete organization $org.\n");
}

/*
 * Delete a login subaccount. Will not delete connected NRENs, but set
 * their respective table column NULL.
 */
function delete_account($login_name) {
  $login_name = sanitize($login_name);
  MDB2Wrapper::execute("DELETE FROM account_map WHERE login_name = ?",
                      array('text'),
                      $login_name
  );

  Logger::log_event(LOG_INFO, "Delete account $login_name.\n");
}

/*
 * Delete a NREN.
 * Notice that this can also delete a lot of institutions due to the
 * ON DELETE CASCADE constraint
 */
function delete_nren($nren_name) {
  $nren_name = sanitize($nren_name);
  MDB2Wrapper::execute("DELETE FROM nrens WHERE name=?",
                       array('text'),
                       $nren_name
  );

  Logger::log_event(LOG_INFO, "Delete NREN $nren_name.\n");
}

/**
 * display a select-box with the elements in choices as the alternative
 * highlight the element passed as 'active'
 */
function display_selectbox($active, $choices, $sel_name)
{
    echo "<select name=\"$sel_name\">\n";

    /* always display an empty option in the select-box */
    echo "<option value=\"\"></option>";
    foreach($choices as $element) {
        if ($element == $active) {
            echo "<option value=\"$element\" selected=\"selected\">" . $element . "</option>\n";
        } else {
            echo "<option value=\"$element\">" . $element . "</option>\n";
        }
    }

    echo "</select>\n";
}

/*
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

/*
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

/*
 * Return a list of all stored login-subaccounts
 */
function get_login_names()
{
  $login_names=array();
  $query = "SELECT login_name FROM account_map";
  $res = MDB2Wrapper::execute($query, NULL, NULL);

  foreach($res as $row) {
    $login_names[] = $row['login_name'];
  }

  return $login_names;
}

/*
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

