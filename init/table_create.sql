-- ---------------------------------------------------------
--
--
-- This is the setup sql script for Confusa
-- It contains the SQL syntax for creating the tables we use
--
-- Only edit this file if you know what you are doing as some of these
-- tables have been more or less hard-coded in the sql-libraries etc.
--
--
-- ---------------------------------------------------------
-- ---------------------------------------------------------
--
-- account_map	 - map an account to a set of username/password credentials.
--
-- The account contains two elements:
--
--	- username
--	- password
--
-- The password is stored in encrypted form, but the encryption should
-- take place in the PHP application, because of flaws in MySQL's
-- AES_ENCRYPT.
--
--   http://moncahier.canalblog.com/archives/2008/01/26/7700105.html
--
-- This is handled by the mcrypt-library, and the start-vector is stored
-- in the last key in the table:
--
--	- ivector
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS account_map (
    account_map_id INT PRIMARY KEY AUTO_INCREMENT,
    -- the login-name for the associated sub-account,
    login_name VARCHAR(128) NOT NULL,

    -- the password with which the sub-account will be accessed.
    -- encrypted at application layer
    password TINYBLOB NOT NULL,

    -- the initialization vector used for encryption the vector must be
    -- random, but need not be confidential. The encryption key (or
    -- passphrase) is stored in the config-file.
    ivector TINYBLOB NOT NULL,
    -- the alliance partner (AP name) by which Comodo identifies it's resellers
    -- this is handed out by Terena in a NREN-specific manner
    ap_name VARCHAR(30) NOT NULL,

    -- NREN reference.
    -- This is so that a NREN can have multiple accounts, and so we can
    -- distinguish between the accounts.
    -- It is important that the CA-accounts are *not* shared amongst the
    -- NRENs, that would be very bad
    nren_id INTEGER NOT NULL,

    foreign key(nren_id) REFERENCES nrens(nren_id) ON DELETE CASCADE

) type=InnoDB;

-- ---------------------------------------------------------
--
-- NRENS - National Research and Educational Network
--
-- Each NREN is supposed to run its own federation. The federation will
-- be tied in via SimpleSAMLphp, and this stores the NRENs currently
-- hooked into confusa, with their corresponding data (CA subaccount for
-- COMODO)
--
-- If Confusa operates in remote-signing mode, it will also use the
-- linked online account for requesting certificates for an
-- organization.
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS nrens (
    nren_id INT PRIMARY KEY AUTO_INCREMENT,
    -- the name of the NREN (e.g. SUNET, UNINETT, FUNET)
    name VARCHAR(30) UNIQUE NOT NULL,

    -- if a remote signing CA is used, the ID of the subaccont there
    login_account INT,
    -- a customized help-text that the NREN may display to its consituency
    help TEXT,
    -- a customized about-message that the NREN may display to its constituency
    about TEXT,
    -- the preferred language for the users within the NREN's domain.
    -- Code according to ISO 639-1, with possible annotation like in de-AT, en-US
    lang VARCHAR(5),
    -- an e-mail-address of somebody who will receive notifications for the NREN
    contact_email VARCHAR(64) NOT NULL,
    contact_phone VARCHAR(24) NOT NULL,
    -- We need to store contact-info for the CERT-team
    cert_email VARCHAR(64),
    cert_phone VARCHAR(16),

    -- The url is the url that will be used for the users contacting
    -- confusa. Even if confusa is hosted at instance.confusa.org, the
    -- NREN might want the users to visit nren.example.org.
    url VARCHAR(128),

    FOREIGN KEY(login_account) REFERENCES account_map(account_map_id) ON DELETE SET NULL
) type=InnoDB;

-- ---------------------------------------------------------
--
-- Subscribers
--
-- Store the subscribers (Universities, colleges etc) that are currently
-- hooked up to Confusa along with their current state (subscribed,
-- suspended, unsubscribed). These are called 'subscribers'
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS subscribers (
    subscriber_id INT PRIMARY KEY AUTO_INCREMENT,
    -- the name of the institution (e.g. KTH, CSC, Univ. of Oslo,...)
    name VARCHAR(30) UNIQUE NOT NULL,
    -- the name that goes into the certificate subject DN (not that the
    -- organization name DN component may fill a maximum of 64 characters
    -- minus 'O=' that is 62 characters
    dn_name VARCHAR(64) UNIQUE NOT NULL,

    -- the NREN as it is stored in the NREN table
    nren_id INT,

    -- the current subscription state to the service
    org_state ENUM('subscribed', 'suspended', 'unsubscribed') NOT NULL,
    -- the preferred language for users belonging to the subscriber
    -- overrides NREN's preferred language for a certain user
    lang VARCHAR(5),

    -- an e-mail address of somebody who will receive notifications for the subscriber
    subscr_email VARCHAR(64) NOT NULL,
    subscr_phone VARCHAR(24) DEFAULT "",
    subscr_resp_name  VARCHAR(24) NOT NULL,
    subscr_resp_email  VARCHAR(24) NOT NULL,
    subscr_comment TEXT DEFAULT "",

    FOREIGN KEY(nren_id) REFERENCES nrens(nren_id) ON DELETE CASCADE
) type=InnoDB;

-- ---------------------------------------------------------
-- If the standalone CA is not used for signing the CSRs, the CSRs are
-- ordered by a service provider (e.g. Comodo).
--
-- Usually this involves some accounting information
--	* order numbers
--	* identifiers for picking the certifiicate up.
--
-- This information should be stored in this table.
--
-- ---------------------------------------------------------
DROP TABLE IF EXISTS order_store;
CREATE TABLE order_store (
	order_number INT PRIMARY KEY,
	-- auth_key and owner for remote download and upload
	auth_key CHAR(64) NOT NULL,
	-- the ePPN of the owner of the ordered certificate
	owner VARCHAR(128) NOT NULL,
	-- order number and collection code for bookkeeping, revocation,
	-- delivery
	order_date DATETIME NOT NULL,
	authorized ENUM('authorized', 'unauthorized', 'unknown') DEFAULT 'unknown',
	expires DATETIME NOT NULL
) type=InnoDB;
-- ---------------------------------------------------------

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
       -- the common-name (CN) of the CSR-owner
       common_name varchar(128) NOT NULL,

       -- for the challenge-response cycle. when we ask the user to approve
       -- the system will generate a one-time password and encrypt it with the
       -- uploaded public-key.
       auth_key char(40) UNIQUE NOT NULL
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
	fingerprint char(40) NOT NULL,

	-- the auth key for remote download of script
	auth_key char(64) UNIQUE NOT NULL,
	cert_owner varchar(64) NOT NULL,
	-- the organization of the cert_owner. Useful for querying for certificates
	-- to-be-revoked e.g. when operating in stand-alone mode
	organization varchar(64) NOT NULL,
	valid_untill DATETIME NOT NULL
) type=InnoDB;

-- admins
--
-- List of people having admin-rights on the page (to update news,
-- watch status of certificates etc)
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
       admin_id INT PRIMARY KEY AUTO_INCREMENT,
       admin varchar(128) NOT NULL, -- ePPN of the admin,

        -- the full name. Will be decoreated when the admin logs in the first time
       admin_name varchar(128) DEFAULT "",
       admin_email varchar(128) DEFAULT "",

       -- level of admin privileges
       -- 2: NREN-admin
       -- 1: Subscriber admin
       -- 0: Subscriber sub-admin (can only revoke for subscriber org. users)
       admin_level ENUM('0','1','2') NOT NULL,

       -- The mode currently associated with the admin
       -- 0: normal mode
       -- 1: administation mode
       --
       -- Note: as an extra injection protection, Person will cast any
       -- updated new_mode to integer, forcing all non-integers to be
       -- '0', i.e. Normal mode.
       last_mode ENUM('0','1') DEFAULT 0,

       -- in order to be able to easily track the hierarchy within administrators
       -- store the subscriber to which the admin belongs.
       -- Leave this field NULL if the admin is a NREN-admin
       subscriber INT,
       -- another nullable field, this time pointing to the NREN.
       -- This field helps to easily determine, for which NREN a NREN-admin is
       -- responsible. It would have been possible to find this information by
       -- joining over the subscriber to the NREN, but it would make bootstrapping
       -- hard, if the subscriber for each NREN-admin was to be known.
       -- The field can be left NULL or filled in if the admin is a subscriber
       -- admin.
       nren INT NOT NULL,
       FOREIGN KEY(subscriber) REFERENCES subscribers(subscriber_id) ON DELETE CASCADE,
       FOREIGN KEY(nren) REFERENCES nrens(nren_id) ON DELETE CASCADE
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
       crl_id INT PRIMARY KEY AUTO_INCREMENT,
       owner varchar(128), -- ePPN of the owner
       cert_sn INT NOT NULL,
       valid_untill DATETIME NOT NULL
) type=InnoDB;

-- ---------------------------------------------------------
-- The map of the attributes.
--
-- We want to allow each NREN (and possibly each subscriber) to create
-- an individual mapping for the attributes. In most cases, it will be
-- one map pr. NREN and none for the subscribers, but experience has
-- taught us that what we think does not always map directly to reality.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS attribute_mapping (
       id INT PRIMARY KEY AUTO_INCREMENT,
       nren_id INT NOT NULL,
       subscriber_id INT,
       eppn varchar(64) NOT NULL,
       epodn varchar(64) NOT NULL,
       cn varchar(64) NOT NULL,
       mail varchar(64) NOT NULL,
       entitlement varchar(64) NOT NULL,
       FOREIGN KEY(subscriber_id) REFERENCES subscribers(subscriber_id) ON DELETE CASCADE,
       FOREIGN KEY(nren_id) REFERENCES nrens(nren_id) ON DELETE CASCADE
) type=InnoDB;


-- ---------------------------------------------------------
-- Robotic Interface Certificate storage area
--
-- This is where the subscriber-admins will have the uploaded
-- certificate stored when using the robotic upload module.
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS robot_certs (
	-- Internal id
       id INT PRIMARY KEY AUTO_INCREMENT,

       -- Reference to the subscriber using this certificate to talk to
       -- confusa
       subscriber_id INT NOT NULL,

       -- ref to the person/admin that uploaded the certificate and most
       -- likely has access to the keypair
       uploaded_by INT NOT NULL,

       -- When the certificate was uploaded
       uploaded_date DATETIME NOT NULL,

       -- This can be found by parsing the certificate, but we should
       -- store this directly in the database as it allows easy testing
       -- for soon-to-expire certificates (as well as finding
       -- certificates with insanely long expiry date).
       valid_until DATETIME NOT NULL,

       -- When the certificate is about to expire, a warning should be
       -- sent to the subscriber in periodic intervals. If the
       -- certificate is about to expire, this is the last time a
       -- warning was sent.
       -- If no warning has been sent, this field should be NULL
       last_warning_sent DATETIME,

       cert TEXT NOT NULL,
       serial char(16) NOT NULL,
       fingerprint char(60) NOT NULL,

       -- Allow for a comment/description to be stored alongside the
       -- certificate.
       comment TEXT,

       FOREIGN KEY(subscriber_id) REFERENCES subscribers(subscriber_id) ON DELETE CASCADE,
       FOREIGN KEY(uploaded_by) REFERENCES admins(admin_id) ON DELETE CASCADE
) type=InnoDB;

-- -----------------------------------------------------------------------------
-- schema_version
--
-- The current DB schema version. Start at 0, increment if something changes in
-- the DB schema in a new release of Confusa.
-- This table can be used to automatically update the schema of a running Confusa
-- instance when a new version with a changed DB-schema is to be installed.
--
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS schema_version (
        version INT PRIMARY KEY DEFAULT 0
) type=InnoDB;
