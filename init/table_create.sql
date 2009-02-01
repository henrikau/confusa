-- ---------------------------------------------------------
--
-- This is the setup sql script for Confusa
-- It contains the SQL syntax for creating the tables we use
--
-- Only edit this file if you know what you are doing as some of these
-- tables have been more or less hard-coded in the sql-libraries etc.
--
-- ---------------------------------------------------------


-- ---------------------------------------------------------
-- 
-- the table describing the sms_auth procedure
-- 
-- This is used for a double-authentication step, where, after
-- authenticated via simplesamlphp (and some federated IdP), another,
-- onetime password is created and sent.
--
-- The data used in this step, are kept here.
--
-- ---------------------------------------------------------
DROP TABLE IF EXISTS sms_auth;
CREATE TABLE sms_auth (
       -- local id in the table
       id INT PRIMARY KEY AUTO_INCREMENT,

       -- the username/uniqe identifer for the user
       -- In feide, this is the eduPersonPrincipalName, which normally has the form of
       -- username@institution.no
       username VARCHAR(64) NOT NULL,

       -- one time password, set for authentication.
       -- When set, a password has been generated and sent to the user.
       -- it is valid untill the time has expired (se below). If session_id is set
       -- at the same time, it's an error. They are mutually exclusive (not easy to
       -- enforce via MySQL..
       --
       -- Also, the password is stored as the SHA-1 sum of the password, which is 160 bits (20 bytes/char)
       -- This is to safeguard the one_time_pass an extra time. This is represented as a hex-sum, so we need
       -- space for 40 characters.
       one_time_pass CHAR(40) NULL,

       -- the id of the session. When set, the user is authenticated
       -- untill the time has expired (see below). 
       -- It contains the session_id() from php.
       --
       -- It is used for auth. when deauthenticating the user. this is done sothat others cannot deauthenticate.
       -- i.e.
       -- Alice has a valid session with slcsweb
       -- Trude want to deauth Alice, and executes the following slcsweb?edu_name='alice@nowhere.org'
       --
       -- If we do not use the session_id, we risk that Alice is (mistakenly) deauthenticated by Trude.
       session_id varchar(40) NULL,

       -- the timeout for either one_time_pass or session_id
       -- given in two variables (date does not want to hold both date and time.
       valid_untill DATETIME DEFAULT '0000-00-00 00:00:00'
) type=InnoDB;

-- ---------------------------------------------------------
--
-- csr_cache
--
-- All CSRs uploaded to the server via the script, are put here. The
-- user must then approve the CSR before it's checked for validity and
-- processed.
-- The CSR should be checked for consistency before it's being stored,
-- however, a few fields cannot be verified before the user has logged in
-- (i.e. the fed. attributes).
--
-- ---------------------------------------------------------
DROP TABLE IF EXISTS csr_cache;
CREATE TABLE csr_cache (
       csr_id INT PRIMARY KEY AUTO_INCREMENT,
       csr TEXT NOT NULL,
       uploaded_date DATETIME NOT NULL,
       from_ip varchar(64) NOT NULL,
       common_name varchar(128) NOT NULL,

       -- for the challenge-response cycle. when we ask the user to approve
       -- the system will generate a one-time password and encrypt it with the
       -- uploaded public-key.
       auth_key char(40) NOT NULL
) type=InnoDB;

-- ---------------------------------------------------------
--
-- cert_cache
--
-- Cache for storing issued certificates. This is useful when we want
-- the automated download of certificates
--
-- ---------------------------------------------------------
DROP TABLE IF EXISTS cert_cache;
CREATE TABLE cert_cache (
	cert_id INT PRIMARY KEY AUTO_INCREMENT,
	cert TEXT NOT NULL,

	-- the auth key for remote download of script
	auth_key char(64) NOT NULL,
	cert_owner varchar(64) NOT NULL,
	valid_untill DATETIME NOT NULL
) type=InnoDB;

-- ---------------------------------------------------------
-- admins
--
-- List of people having admin-rights on the page (to update news,
-- watch status of certificates etc)
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
       admin char(128) PRIMARY KEY -- eduPersonPrincipalName of admin
) type=InnoDB;

 
-- ---------------------------------------------------------
--
-- pubkeys
--
-- This table holds the hash of *all* public-keys ever issued from this
-- Service.
--
-- As it is a *requirement* to *never* re-issue a certificate (or resign
-- the same key twice, we must create this table in order to guarantee
-- this. This also implies that the table should not be dropped
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS pubkeys (
       -- the hash of the (signed) public key
       -- sothat we can ensure that the same key isn't signed twice (or more)
       pubkey_hash char(40) PRIMARY KEY,

       -- The time when the certificate was first signed.
       signed DATETIME NOT NULL,

       -- the number of times the key has been uploaded. If this number gets very large,
       -- it should be a cause for concern (i.e. another openssl weakness)
       uploaded_nr int DEFAULT 1
) type=InnoDB;


-- ---------------------------------------------------------
--
-- user_crls
--
-- Holds the list of serial-numbers for issued certificates
-- so that the user may revoke them when needed.
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_crls (
       owner varchar(128) PRIMARY KEY,
       cert_sn INT NOT NULL,
       valid_untill DATETIME NOT NULL
) type=InnoDB;
