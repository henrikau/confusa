<?php
ini_set("include_path", ini_get('include_path') . ":../config/:../lib/misc:../lib/exceptions/:../lib/file/");

require_once 'mdb2_wrapper.php';
require_once 'config.php';

/**
 * _Bootstrap script for initial insertion of a NREN admin.
 *
 * - Insert the administrator for a NREN into the DB. The NREN is created if
 * it doesn't exist yet
 *
 *
 * Execute directly from command line this way:
 *      php -f bootstrap_nren.php <nren_name> <username> <password>
 *
 *
 */

echo "Running " . $argv[0] . " to bootstrap the database with values\n";
if ($argc === 4) {
	insert_nren_admin($argv[1], $argv[2], $argv[3]);
}
else {
	echo "Please call that file from the corresponding shellscript!\n";
	exit(5);
}

/*
 * Get the NREN id for the NREN stored with name nren_name in the DB
 *
 * @param $nren_name The name with which the NREN is stored in the DB
 */
function get_nren_id($nren_name) {
	$query = "SELECT nren_id FROM nrens WHERE name=?";
	$res = NULL;

	try {
		$res = MDB2Wrapper::execute($query,
									array('text'),
									array($nren_name));
	} catch (DBStatementException $dbse) {
		echo "Could not query the DB for the NREN $nren_name! Internal error was " . $dbse->getMessage();
		exit(5);
	} catch (DBQueryException $dbqe) {
		echo "Could not query the DB for the NREN $nren_name! Probably an issue with with the data. " .
			 "Problem was: " . $dbqe->getMessage();
		exit(5);
	}

	return $res;
}

/**
 * insert_nren_admin - insert a new administrator for an nren. If the NREN does
 * not exist, create it.
 *
 * @param $nren_name the name of the nren to use.
 * @param $principal eduPersonPrincipalName or similar unique identifier for the
 *			administrator that is to be added
 * @param $contact An e-mail address at the NREN which can be contacted if contact
 * 	is necessary
 */
function insert_nren_admin($nren_name, $principal, $contact)
{

	$res = get_nren_id($nren_name);

	if (count($res) == 0) {
		$statement = "INSERT INTO nrens(name, contact) VALUES(?,?)";

		try {
			MDB2Wrapper::update($statement,
								array('text', 'text'),
								array($nren_name, $contact));

			$res = get_nren_id($nren_name);
		} catch (DBStatementException $dbse) {
			echo "Could not insert the NREN $nren_name into the DB! Internal error was " . $dbse->getMessage();
			exit(5);
		} catch (DBQueryException $dbqe) {
			echo "Could not insert the NREN $nren_name into the DB! " .
			"Does the NREN already exist? Problem was: " . $dbqe->getMessage();
			exit(5);
		}
	}

	$insert_admin_query = "INSERT INTO admins(admin, admin_level, nren) VALUES(?,?,?)";

	try {
		MDB2Wrapper::update($insert_admin_query,
							array('text','text','text'),
							array($principal, 2, $res[0]['nren_id']));
	} catch (DBStatementException $dbse) {
		echo "Could not insert admin $principal into the DB! Internal error was " . $dbse->getMessage();
		exit(5);
	} catch (DBQueryException $dbqe) {
		echo "Could not insert admin $prinipal into the DB! Problem was: " . $dbqe->getMessage();
	}
}
