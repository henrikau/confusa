<?php
include_once('framework.php');
include_once('mdb2_wrapper.php');
include_once('person.php');

/* CRL reason codes according to RFC 3280. Those having no real meaning for NREN
 * and institution admins have been removed from this list. */
$nren_reasons = array('unspecified', 'keyCompromise', 'affiliationChanged',
            'superseeded', 'certificateHold', 'privilegeWithdrawn',
            'aACompromise');

$fw = new Framework('admin');
$fw->force_login();
$fw->render_page();

function admin($person) {
    if ($person->is_institution_admin()) {
        if (isset($_GET['revoke'])) {
            switch($_GET['revoke']) {
            case 'manage':
                search_certs_mask();
                break;

            case 'search':
                search_certs_mask();
                search_certs($_POST['search'], $person);
                break;

            case 'doRevoke':
                revoke_certs($_POST['order_numbers'], $_POST['reason']);
                break;
            default:
                echo "Unknown operation<BR />\n";
                break;
            }
        }
    } else {
        throw new ConfusaGenException("Insufficient rights for revocation!");
    }
}
/*
 * Display a search mask for certificates.
 * All search terms are treated as wildcard searchs.
 */
function search_certs_mask()
{
    echo "Search for commonName:";
    echo "<form action=\"?revoke=search\" method=\"POST\">";
    echo "<input type=\"text\" name=\"search\" value=\"\" />";
    echo "<input type=\"submit\" name=\"Search\" value=\"Search\" />";
    echo "</form>";
}

/*
 * Perform the search for a certain common name. Use the organization of the
 * person as a search restriction.
 *
 * @param $common_name The common-name that is searched for. Will be automatically
 *                     turned into a wildcard
 * @param $person The person who is performing the search
 */
function search_certs($common_name, $person)
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
            $owners[] = $row['owner_subject'];
            $orders[$row['owner_subject']][] = $row['auth_key'];
        }

        $owners = array_unique($owners);

        echo $tr;
        echo "$td<b>Full Subject DN</b>$td_e$td<b>Revocation reason</b>$td_e$td$td_e";
        echo "$tr_e";

        foreach($owners as $owner) {
            echo "$tr";
            echo "$td";
            echo "<form action=\"?revoke=doRevoke\" method=\"POST\">";

            echo $owner . "\n";
            echo "$td_e$td";
            foreach ($orders[$owner] as $order) {
                echo "<input type=\"hidden\" name=\"order_numbers[]\" value=\"$order\" />";
            }

            display_selectbox('unspecified', $nren_reasons, "reason");
            echo "$td_e$td";
            echo "<input type=\"submit\" name=\"submit\" value=\"Revoke All\" />";
            echo "$td_e";
            echo "</form>";
            echo $tr_e;
        }

        echo $table_e;
    }
}

/*
 * Revoke all the certificates in $auth_key_list with the supplied reason
 *
 * @param $auth_key_list The references to the certificates that are to be
 *                       revoked
 * @param $reason The reason for revocation as defined in RFC 3280
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

    foreach($auth_key_list as $auth_key) {
        $cm->revoke_cert($auth_key, $reason);
    }
}

/*
 * Remove anything that could be dangerous from user input.
 * Our organization names should contain only [a-z][0-9], like the nren
 * names, like the states. So all inputs can be limited to [a-z][0-9]
 *
 * TODO: This function should be accessible for all forms taking data
 * @param $input The input which is going to be sanitized
 */
function sanitize($input)
{
  if (is_array($input)) {
    foreach($input as $var=>$val) {
      $output[$var] = sanitize($val);
    }
  }
  /* also allow the wildcard character */
  $output = preg_replace('/[^a-z0-9 @.%]+/i','',$input);
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
