<?php
include_once('framework.php');
include_once('mdb2_wrapper.php');
include_once('person.php');
include_once('csv_lib.php');

/* CRL reason codes according to RFC 3280. Those having no real meaning for NREN
 * and institution admins have been removed from this list. */
$nren_reasons = array('unspecified', 'keyCompromise', 'affiliationChanged',
            'superseeded', 'certificateHold', 'privilegeWithdrawn',
            'aACompromise');

$fw = new Framework('admin');
$fw->force_login();
$fw->render_page();

/**
 * Dispatcher function that calls the respective functions in the script
 * based on the query_string it receives.
 * Furthermore it checks if the calling user has sufficient permissions to
 * perform the operations
 */
function admin($person) {
    if ($person->is_institution_admin()) {
        try {
            if (isset($_GET['revoke'])) {
                switch($_GET['revoke']) {
                case 'manage':
                    search_certs_mask();
                    upload_list_mask();
                    break;

                case 'search_display':
                    search_certs_mask();
                    search_certs_display($_POST['search'], $person);
                    break;

                case 'do_revoke':
                    revoke_certs($_POST['order_numbers'], $_POST['reason']);
                    search_certs_mask();
                    upload_list_mask();
                    break;

                case 'search_list_display':
                    upload_list_mask();
                    search_list_display('eppn_list', $person);
                    break;

                case 'do_revoke_list':
                    revoke_list($_POST['reason']);
                    search_certs_mask();
                    upload_list_mask();
                    break;

                default:
                    echo "Unknown operation<BR />\n";
                    break;
                }
            }
        } catch (ConfusaGenException $e) {
            echo $e->getMessage() . "<br />\n";
        }
    } else {
        throw new ConfusaGenException("Insufficient rights for revocation!");
    }
}
/**
 * Display a search mask for certificates.
 */
function search_certs_mask()
{
    echo "Search for commonName:";
    echo "<form action=\"?revoke=search_display\" method=\"POST\">";
    echo "<input type=\"text\" name=\"search\" value=\"\" />";
    echo "<input type=\"submit\" name=\"Search\" value=\"Search\" />";
    echo "</form>";
}

/**
 * Display a mask for CSV-file upload
 */
function upload_list_mask()
{
    $file_name = "eppn_list";
    echo "Or upload a list with eduPersonPrincipalNames to revoke:<br />";
    echo "<form enctype=\"multipart/form-data\" action=\"?revoke=search_list_display\" method=\"POST\">";
    echo "<input type=\"hidden\" name=\"max_file_size\" value=\"10000000\" />";
    echo "<input name=\"$file_name\" type=\"file\" />";
    echo "<input type=\"submit\" value=\"Upload list\" />";
    echo "</form>";
}

/*
 * Perform the search for a certain common name. Use the organization of the
 * person as a search restriction. Display the result along with a revoke-
 * option in a list grouped by different certificate owners.
 *
 * @param $common_name The common-name that is searched for. Will be automatically
 *                     turned into a wildcard
 * @param $person The person who is performing the search
 */
function search_certs_display($common_name, $person)
{
    global $fw;
    global $nren_reasons;
    $table="<div class=\"admin_table\">\n";
    $tr="<div class=\"admin_table_row\">\n";
    $td="<div class=\"admin_table_cell\">\n";
    $table_e = "</div>\n";
    $tr_e = "</div>\n";
    $td_e = "</div>\n";

    $org = $person->get_orgname();

    $common_name = sanitize($common_name);

    $common_name = "%" . $common_name . "%";
    $cm = $fw->get_cert_manager();
    $certs = $cm->get_cert_list_for_persons($common_name, $org);

    if (count($certs) > 0) {
        echo $table;

        /* get the certificate owner/order number pairs into a ordering that
         * permits us to send the order-numbers for each certificate owner
         * to the revocation method */
        foreach($certs as $row) {
            $owners[] = $row['cert_owner'];
            $orders[$row['cert_owner']][] = $row['auth_key'];
        }

        $owners = array_unique($owners);

        echo $tr;
        echo "$td<b>Full Subject DN</b>$td_e$td<b>Revocation reason</b>$td_e$td$td_e";
        echo "$tr_e";

        foreach($owners as $owner) {
            echo "$tr";
            echo "$td";
            echo "<form action=\"?revoke=do_revoke\" method=\"POST\">";

            echo $owner . "\n";
            echo "$td_e$td";
            foreach ($orders[$owner] as $order) {
                echo "<input type=\"hidden\" name=\"order_numbers[]\" value=\"$order\" />";
            }

            display_selectbox('unspecified', $nren_reasons, "reason");
            echo "$td_e$td";
            echo "<input type=\"submit\" name=\"submit\" value=\"Revoke All\"" .
                 "onclick=\"return confirm('Are you sure?')\"/>";
            echo "$td_e";
            echo "</form>";
            echo $tr_e;
        }

        echo $table_e;
    }
}

/**
 * Display a list of distinguished names whose certificates will be revoked
 * based on an uploaded CSV with a list of eduPersonPrincipalNames. Offer the
 * possibility to revoke these certificates.
 *
 * @param string $eppn_file The name of the $_FILES parameter containining the
 *                          CSV of eduPersonPrincipalNames
 * @param Person $person The person calling this function
 *
 * @throws FileException if something goes wrong in parsing the CSV file
 */
function search_list_display($eppn_file, $person)
{
    /* These can become a *lot* of auth_keys/order_numbers. Thus, save the list
     * of auth_keys preferrably in the session, otherwise it will take forever
     * to download the site and I am not sure if it is such a good idea to send
     * an endless list of auth_keys as hidden parameters
     * to the user and then from there back again with a POST to the server
     */
    if (isset($_SESSION['auth_keys'])) {
        unset($_SESSION['auth_keys']);
    }

    global $fw;
    $cm = $fw->get_cert_manager();
    global $nren_reasons;

    $csvl = new CSV_Lib($eppn_file);
    $eppn_list = $csvl->get_csv_entries();

    $org = $person->get_orgname();
    $certs = array();
    $auth_keys = array();

    foreach($eppn_list as $eppn) {
        $eppn = sanitize_eppn($eppn);
        $eppn = "%" . $eppn . "%";
        $eppn_certs = $cm->get_cert_list_for_persons($eppn, $org);
        $certs = array_merge($certs, $eppn_certs);
    }

    if (count($certs) > 0) {
        echo "<b>The following DNs are going to be revoked:</b><br />\n";
        echo "<div class=\"spacer\"></div>";
        echo "<table class=\"small\">";
         /* get the certificate owner/order number pairs into a ordering that
         * permits us to send the order-numbers for each certificate owner
         * to the revocation method */
        foreach($certs as $row) {
            $owners[] = $row['cert_owner'];
            $auth_keys[] = $row['auth_key'];
        }

        $owners = array_unique($owners);

        foreach($owners as $owner) {
            echo "<tr style=\"width: 80%\"><td>";
            echo $owner;
            echo "</td></tr>";
        }
        echo "</table>";

        echo "<div class=\"spacer\"></div>";

        echo "<div style=\"text-align: right\">";
        echo "<form action=\"?revoke=do_revoke_list\" method=\"POST\">";
        echo "Revocation reason: ";
        display_selectbox('unspecfied', $nren_reasons, 'reason');
        $_SESSION['auth_keys'] = $auth_keys;
        echo "<input type=\"Submit\" value=\"Revoke all\"" .
             "onclick=\"return confirm('Are you sure?')\" />";
        echo "</form>";
        echo "</div>";
    }
}

/*
 * Revoke all the certificates in $auth_key_list with the supplied reason
 *
 * @param $auth_key_list The references to the certificates that are to be
 *                       revoked
 * @param $reason The reason for revocation as defined in RFC 3280
 *
 * @throws ConfusaGenException If revocation fails or the revocation reason is
 *                             unrecognized
 */
function revoke_certs($auth_key_list, $reason)
{
    global $fw;
    global $nren_reasons;
    $auth_key_list = sanitize($auth_key_list);

    if (array_search($reason, $nren_reasons) === FALSE) {
        throw new ConfusaGenException("Encountered an unknown revocation " .
                                      "reason!"
        );
    }

    $cm = $fw->get_cert_manager();

    $num_certs = count($auth_key_list);

    Logger::log_event(LOG_INFO, "Trying to revoke $num_certs certificates." .
                                "Administrator contacted us from " .
                                $_SERVER['REMOTE_ADDR']
    );

    foreach($auth_key_list as $auth_key) {
        $cm->revoke_cert($auth_key, $reason);
    }

    Logger::log_event(LOG_NOTICE, "Successfully revoked $num_certs certificates." .
                                  "Administrator contacted us from " .
                                  $_SERVER['REMOTE_ADDR']
    );
}

/**
 * Revoke a list of certificates possibly belonging to more than one end-entity
 * based on an array of auth_keys stored in the session. Based on the number of
 * certificates that are going to be revoked, this may take some time.
 *
 * @param string $reason The reason for revocation (as in RFC 3280)
 *
 * @throws ConfusaGenException If the auth_keys are not found in the session,
 *                             there is a problem with revocation or if the
 *                             reason is unknown
 */
function revoke_list($reason)
{
    global $fw;
    global $nren_reasons;

    $cm = $fw->get_cert_manager();

    $auth_keys = array();
    if (isset($_SESSION['auth_keys'])) {
        $auth_keys = $_SESSION['auth_keys'];
        unset($_SESSION['auth_keys']);
    } else {
        throw new ConfusaGenException("Lost session! Please log-out of Confusa, " .
                                      "log-in again and try again!\n");
    }

    if (array_search($reason, $nren_reasons) === false) {
        throw new ConfusaGenException("Unknown reason for certificate revocation!");
    }

    $num_certs = count($auth_keys);

    Logger::log_event(LOG_INFO, "Trying to revoke $num_certs certificates." .
                                "Administrator contacted us from " .
                                $_SERVER['REMOTE_ADDR'] .
                                " in a bulk (list) revocation request."
    );

    foreach($auth_keys as $auth_key) {
        $cm->revoke_cert($auth_key, $reason);
    }

    Logger::log_event(LOG_INFO, "Successfully revoked $num_certs certificates." .
                                "Administrator contacted us from " .
                                $_SERVER['REMOTE_ADDR'] .
                                " in a bulk (list) revocation request."
    );
}

/**
 * Remove anything that could be dangerous from user input.
 * Common-name search patterns should contain only [a-z][0-9] @.
 * So all inputs can be limited to [a-z][0-9] @.
 *
 * @param $input The input which is going to be sanitized
 */
function sanitize($input)
{
  if (is_array($input)) {
    foreach($input as $var=>$val) {
      $output[$var] = sanitize($val);
    }
  }
  /* also allow the wildcard character and the e-mail character*/
  $output = preg_replace('/[^a-z0-9 @.]+/i','',$input);
  return $output;
}

/**
 * Limit the eduPersonPrincipalName to a set of expected characters.
 *
 * @param mixed $eppn An eduPersonPrincipalName or an array therof
 *
 * @return The sanitized string/array
 */
function sanitize_eppn($eppn)
{
    if (is_array($eppn)) {
        foreach($eppn as $var=>$val) {
          $output[$var] = sanitize_eppn($val);
        }
    }
  /* also allow the the e-mail characters @, . and _ */
  $output = preg_replace('/[^a-z0-9@._]+/','',$eppn);
  return $output;
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

?>
