<?php
ini_set("include_path", ini_get('include_path') . ":../config/:../lib/misc:../lib/exceptions/:../lib/file/");

require_once 'mdb2_wrapper.php';
require_once 'config.php';

/**
 *  Bootstrap script for remote-CA accounts of NRENs.
 *
 * - Insert login name and mcrypt-encrypted password into DB
 * - Create a NREN called 'uninett'
 * - Link institutions 'feide' and 'openidp' to 'uninett' for testing
 *
 * Serves as a placeholder until the respective code is provided in a
 * real bootstrap script
 *
 * Execute directly from command line this way:
 *      php -f bootstrap_account.php <nren_name> <username> <password>
 *
 *
 */

echo "Running " . $argv[0] . " to bootstrap the database with values\n";
if ($argc === 4) {
	insert_credentials($argv[1], $argv[2], $argv[3]);
}
else {
	show_help($argv);
	exit(5);
}

/**
 * insert_credentials - insert credentials for a nren
 *
 * @nren_name		: the name of the nren to use.
 * @login_name		: login to COMODO-account for NREN. This is a key to
 *			  account_map, which means that an NREN can only have *one*
 *			  account, but one account can have many NRENS
 * @login_password	: password for the login to comodo
 */
function insert_credentials($nren_name, $login_name, $login_password)
{
	/* The the encryption key */
	$enckey		= Config::get_config('capi_enc_pw');
	$size		= mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB);
	$iv		= mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
	$login_pw	= base64_encode($login_password);

	if ($enckey === "") {
		echo "You must set the encryption key before we can bootstrap the NREN ($nren_name)\n";
		exit;
	}

    /*
    * It may seem overblown to base64-encode first the password and then
    * the encryption result again. But testing revealed that
    * insertion can fail sometimes if the encryption string is not b64-encoded.
    * It doesn't take very long to b64-encode and it makes inserting the
    * encrypted account into the DB safe.
    */
    $cryptpw = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$enckey,
					    $login_pw,
					    MCRYPT_MODE_CFB,
					    $iv
				     ));

    $query  = "INSERT INTO account_map (login_name, password, ivector)";
    $query .= "VALUES(?, ?, ?)";

    try {
	MDB2Wrapper::update($query,
			    array('text','text','text'),
			    array($login_name, $cryptpw, base64_encode($iv)));
    } catch (DBStatementException $dbse) {
	echo "Could not insert the supplied login name into the account-map table! Problem " . $dbse->getMessage();
	exit(5);
    } catch (DBQueryException $dbqe) {
	echo "Could not insert the supplied login name into the account-map table! Problem " . $dbse->getMessage();
	exit(5);
    }

    echo "Inserted values to account_map. Moving on to update the NREN\n";

    $pk_query = "SELECT account_map_id FROM account_map WHERE login_name=?";
    $res = NULL;

    try {
	$res = MDB2Wrapper::execute($pk_query,
				    array('text'),
				    array($login_name));
    } catch (DBStatementException $dbse) {
	echo "Could not get the ID of the inserted account. This looks serious..." . $dbse->getMessage();
	exit(5);
    } catch (DBQueryException $dbqe) {
	echo "Could not get the ID of the inserted account. This looks serious..." . $dbqe->getMessage();
	exit(5);
    }

    if (count($res) == 1) {
	$account_id = $res[0]['account_map_id'];

	$nren_query = "INSERT INTO nrens (login_account, name) VALUES(?, ?)";
	try {
		MDB2Wrapper::update($nren_query,
				    array('text', 'text'),
				    array($account_id, strtolower($nren_name)));
	} catch (DBStatementException $dbse) {
		echo "Could not insert the NREN with it's login account. Database said " . $dbse->getMessage();
		exit(5);
	} catch (DBQueryException $dbqe) {
		echo "Could not insert the NREN with it's login account. Database said " . $dbqe->getMessage();
		exit(5);
	}

	echo "Inserted NREN, connected to " . strtolower($nren_name) . "\n";

    } else {
	echo "An error occured while inserting the bootstrapped account!";
	exit(5);
    }

}

function show_help($argv)
{
	echo "Usage: " . $argv[0] . "<nren_name> <login_name> <login_password>\n";
	echo "\tnren_name:\tThe name of the NREN, e.g. UNINETT\n";
	echo "\tlogin_name:\tThe username for the CA-account name\n";
	echo "\tlogin_password:\tThe password tied tot he CA-account\n";
}

?>
