<?php
ini_set("include_path", ini_get('include_path') . ":../config/:../lib/misc:../lib/exceptions/:../lib/file/");

require_once 'mdb2_wrapper.php';
require_once 'config.php';

/**
 * _*DUMMY* bootstrap script.
 *
 * - Insert login name and mcrypt-encrypted password into DB
 * - Create a NREN called 'uninett'
 * - Link institutions 'feide' and 'openidp' to 'uninett' for testing
 *
 * Serves as a placeholder until the respective code is provided in a
 * real bootstrap script
 *
 * Execute directly from command line this way:
 *      php -f bootstrap_account.php <username> <password>
 *
 */

if ($argc === 3) {
    insert_credentials($argv[1], $argv[2]);
}

function insert_credentials($login_name, $login_password)
{
    $enckey = Config::get_config('capi_enc_pw');
    $size=mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
    $iv=mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
    $login_password = base64_encode($login_password);

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

    $nren_query = "INSERT INTO nrens(login_name, name) VALUES(?, 'uninett')";
    MDB2Wrapper::update($nren_query, array('text'), array($login_name));

    $institution1 = "INSERT INTO institutions(name, nren_name, org_state) VALUES('openidp','uninett', 'subscribed')";
    $institution2 = "INSERT INTO institutions(name, nren_name, org_state) VALUES('feide', 'uninett', 'subscribed')";

    MDB2Wrapper::update($institution1, NULL, NULL);
    MDB2Wrapper::update($institution2, NULL, NULL);

}
?>
