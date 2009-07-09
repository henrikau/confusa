<?php

require_once('person.php');
require_once('cert_manager.php');
require_once('key_sign.php');
require_once('mdb2_wrapper.php');
require_once('db_query.php');

/*
 * CertManager_Standalone Standalone-CA extension for CertManager.
 *
 * Class for signing CSRs with locally available CA certificates and storing, retrieving
 * and listing the issued certificates.
 *
 * PHP version 5
 * @author: Henrik Austad <henrik.austad@uninett.no>
 * @author: Thomas Zangerl <tzangerl@pdc.kth.se>
 */
class CertManager_Standalone extends CertManager
{
    /**
     * Verify if the subject DN matches the received sets of attributes.
     * Sign a key using the local CA-key.
     * Store the public key of the request in the database.
     *
     * @throws: KeySignException
     */
    public function sign_key($auth_key, $csr)
    {
        if ($this->verify_csr($csr)) {
            $cert_path = 'file://'.dirname(WEB_DIR) . Config::get_config('ca_cert_path') . Config::get_config('ca_cert_name');
            $ca_priv_path = 'file://'.dirname(WEB_DIR) . Config::get_config('ca_key_path') . Config::get_config('ca_key_name');

            $cert = null;
            $sign_days = 11;
            $tmp_cert = openssl_csr_sign($csr, $cert_path, $ca_priv_path, $sign_days , array('digest_alg' => 'sha1'));
            openssl_x509_export($tmp_cert, $cert, true);

            MDB2Wrapper::update("INSERT INTO cert_cache (cert, auth_key, cert_owner, organization, valid_untill) VALUES(?, ?, ?, ?, addtime(current_timestamp(), ?))",
                                array('text', 'text', 'text', 'text'),
                                array($cert, $auth_key, $this->person->get_valid_cn(), $this->person->get_orgname(), Config::get_config('cert_default_timeout')));
            Logger::log_event(LOG_INFO, "Certificate successfully signed for ".
                                $this->person->get_valid_cn() .
                                " Contacting us from ".
                                $_SERVER['REMOTE_ADDR']);

        } else {
          Logger::log_event(LOG_INFO, "Will not sign invalid CSR for user ".
                       $this->person->get_valid_cn() .
                       " from ip ".$_SERVER['REMOTE_ADDR']);
          throw new KeySignException("CSR subject verification failed!");
        }

         /* read public key and create sum */
	    $pubkey_checksum = pubkey_hash($csr, true);
        MDB2Wrapper::update("INSERT INTO pubkeys (pubkey_hash, uploaded_nr) VALUES(?, 0)",
                            array('text'),
                            array($pubkey_checksum));
    } /* end sign-key */

    /**
     * Retrieve a list of the certificates associated with the managed person
     * from the database
     *
     * @throws DBQueryException
     */
    public function get_cert_list()
    {
        $res = MDB2Wrapper::execute("SELECT auth_key, cert_owner, valid_untill FROM cert_cache WHERE cert_owner=? AND valid_untill > current_timestamp()",
              array('text'),
              array($this->person->get_valid_cn()));

        $num_received = count($res);

        if ($num_received > 0 && !(isset($res[0]['auth_key']))) {
            $msg = "Received an unexpected response from the database for user " .
                     $this->person->get_common_name();
            throw new DBQueryException($msg);
        }

        return $res;
    } /* end get_cert_list */


    /**
     * Get a list of certificates for all the persons matched by the $common_name,
     * which may include one or more '%' wildcard characters.
     *
     * @param string $common_name Query for certificate owners with a certain
     *        common name, possibly including one or more '%' wildspace
     *        characters
     * @param string $org The organization to which the search is restricted
     *
     * @return Array with results with entries of the form
     *          array('cert_owner','auth_key')
     */
    public function get_cert_list_for_persons($common_name, $org) {
        $query = "SELECT auth_key, cert_owner FROM cert_cache WHERE " .
                 "cert_owner LIKE ? AND organization = ?";
        $res = MDB2Wrapper::execute($query, array('text','text'),
                                            array($common_name, $org)
        );

        return $res;
    }

    /*
     * Get the certificate bound to key $key from the database
     *
     * @throws ConfusaGenException
     */
    public function get_cert($key)
    {
        echo "key " . $key . " person_cn " . $this->person->get_valid_cn() . " <br />\n";
        $res = MDB2Wrapper::execute("SELECT cert FROM cert_cache WHERE auth_key=? AND cert_owner=? AND valid_untill > current_timestamp()",
                                      array('text', 'text'),
                                      array($key, $this->person->get_valid_cn()));

        if (count($res) == 1) {
            Logger::log_event(LOG_NOTICE, "Sending certificate with hash " . pubkey_hash($res[0]['cert'], false) . " and auth-token $authvar to user from ip " . $_SERVER['REMOTE_ADDR']);
            return $res[0]['cert'];
        }
        else {
            $msg = "Error in getting certificate, got " . count($res) . " results\n";
            $cn = $this->person->get_valid_cn();
            $msg .= "Queried for key $key and CN $cn\n";
            throw new DBQueryException($msg);
        }
    }

    /*
     * Revoke the certificate identified by key
     * Key is an auth_var
     */
    public function revoke_cert($key, $reason)
    {
        /* TODO: method stub
         *
         * At a first glance there seems to be no revoke function in php-openssl.
         * shell_exec('openssl ca -revoke...') would be possible but... eew...
         * Generously leaving this decision to Henrik ;-)
         *
        */
        echo "Revocation for standalone configuration is to be implemented!";
    }
} /* end class CertManager_Standalone */
?>
