#!/usr/bin/env php5
<?php
require_once dirname(__FILE__) . "/../www/confusa_include.php";
require_once "MDB2Wrapper.php";

function getNRENs()
{
	$q = "SELECT name, nren_id FROM nrens";
	return MDB2Wrapper::execute($q, NULL, NULL);
}

function testUpdateNREN($nren_id)
{
	/* if columns missing, no update */
	if (MDB2Wrapper::testColumn('nrens', 'login_name') &&
		MDB2Wrapper::testColumn('nrens', 'password') &&
		MDB2Wrapper::testColumn('nrens', 'ivector') &&
		MDB2Wrapper::testColumn('nrens', 'ap_name')) {
		return true;
	}
	return false;
}
function hasNewValues($nren_id)
{
	$q = "SELECT login_name, password, ivector, ap_name FROM nrens WHERE nren_id=?";
	$res = MDB2Wrapper::execute($q, array('integer'), array($nren_id));
	if (count($res) == 1) {
		if (!empty($res[0]['login_name'] ) && $res[0]['login_name'] !== "" &&
			!empty($res[0]['password'] ) && $res[0]['password'] !== "" &&
			!empty($res[0]['ivector'] ) && $res[0]['ivector'] !== "" &&
			!empty($res[0]['ap_name'] ) && $res[0]['ap_name'] !== "") {
			return true;
		}
	}
	return false;
}

function getAccountsForNREN($nren_id)
{
	$q = "SELECT * FROM account_map WHERE nren_id=?";
	return MDB2Wrapper::execute($q, array('integer'), array($nren_id));
}

function migrateAccountMap($nren_id, $username, $password, $ivector, $ap_name)
{
	if (hasNewValues($nren_id)) {
		echo "It looks like NREN " . $nren_id . " " .
			"has been migrated already, please verify this and if not, " .
			"clean the fields in the database before venturing forth.\n";
	} else {
		echo "Migrating NREN " . $nren_id . " to new schema.\n";
		$u = "UPDATE nrens SET login_name=?, ap_name=?, password=?, ivector=? WHERE nren_id=?";
		MDB2Wrapper::update($u,
							array('text', 'text', 'text', 'text', 'integer'),
							array($username, $ap_name, $password, $ivector, $nren_id));
	}
}

$nrens = getNRENs();
foreach ($nrens as $idx => $nren) {
	if (testUpdateNREN($nren['nren_id'])) {
		$accounts = getAccountsForNREN($nren['nren_id']);
		if (count($accounts) == 0) {
			echo "No account found for NREN " . $nren['nren_id'] . ", won't migrate nothing..\n";
			continue;
		} else if (count($accounts) > 1) {
			echo "Multiple accounts found for NREN " . $nren['nren_id'] . "(" . $nren['name'] . ")" .
				". Will only migrate account 0 - make sure this is the correct account afterwards!\n";
			echo "Raw-dump of all accounts found:\n";
			print_r($accounts);
		}
		migrateAccountMap($nren['nren_id'],
						  $accounts[0]['login_name'],
						  $accounts[0]['password'],
						  $accounts[0]['ivector'],
						  $accounts[0]['ap_name']);
	}
}
?>