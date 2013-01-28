
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

-- When we create the database, we must temporarily disable foreign-key
-- check as it will fail upon creating account_map as nrens has not been
-- created yet, and vice versa.
--
-- This is re-enabled at the end of the file.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
--
-- NRENS - National Research and Educational Network
--
-- Each NREN is supposed to run its own federation. The federation will
-- be tied in via SimpleSAMLphp, and this stores the NRENs currently
-- hooked into confusa, with their corresponding data (CA subaccount for
-- COMODO)
--
-- If Confusa uses Comodo as its CA, it will also use the
-- linked online account for requesting certificates for an
-- organization.
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS nrens (
    nren_id INT PRIMARY KEY AUTO_INCREMENT,
    -- the name of the NREN (e.g. SUNET, UNINETT, FUNET)
    name VARCHAR(30) UNIQUE NOT NULL,

    -- The country-code of the NREN
    --
    -- E.g 'NO' or 'US'
    country CHAR(2) NOT NULL,

    -- Note: there will be several instances out there with the field
    -- 'login_account' in this table instead of the fields directly in this table.

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

    -- a customized help-text that the NREN may display to its consituency
    help TEXT,
    -- a customized about-message that the NREN may display to its constituency
    about TEXT,

    -- A customized privacy-notice that the NREN may display to its
    -- constituency.
    -- If this is not set, Confusa will take the default privacy-notice.
    privacy_notice TEXT,

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
    -- The WAYF-URL is the URL of the NREN's own WAYF. This will assist the
    -- user in picking the IdP with which they want to authenticate.
    wayf_url VARCHAR(128),

    -- The certificates are capable of storing one or more emails in the
    -- subject altname. However, this should be configurable for the
    -- NREN admins.
    --
    -- All t hese emails must be masked to the exported values to stop
    -- users to include random addresses in the cert.
    --
    -- 0 : no certificate at all
    -- 1 : one, and only one.
    -- n : multiple, or none, total user freedom.
    -- m : multiple, but at least one.
    enable_email ENUM('0', '1', 'n', 'm') DEFAULT '1',
    -- The certificate validity. In test-mode this is always 14 days. For
    -- personal certificates it will be one of 365, 730 or 1095 days, for
    -- productive e-Science certs always 395 days
	-- This will be filled by the bootstrap_nren script
    cert_validity ENUM('365', '730', '1095'),
    -- The title of the portal that is shown on the NREN-specific page.
    -- Here NRENs have the possibility to override the generic
    -- "TCS eScience portal" for their branded Confusa view
    show_portal_title BOOLEAN DEFAULT TRUE,
    portal_title VARCHAR(35),
    -- The timeout in minutes, after which Confusa will ask for reauthentication
    -- upon sensitive actions.
    reauth_timeout INT DEFAULT 10,

    -- Message (and mode-switch) to allow an NREN-admin to place the
    -- portal (for the given NREN only) in maintenance-mode.
    maint_msg TEXT DEFAULT "",
    maint_mode ENUM('y','n') DEFAULT 'n',

    FOREIGN KEY(login_account) REFERENCES account_map(account_map_id) ON DELETE SET NULL
) engine=InnoDB;


-- ---------------------------------------------------------
--
-- idp_map
--
-- The IdP-Map is what we use to connect an IdP to an NREN. Since one
-- NREN can contain several IdPs, but one IdP may only belong to a
-- single NRNEN, we reference the NREN from this table.
--
-- The idp_url must match the index used in
-- metadata/saml20-idp-remote.php in SimpleSAMLphp.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS idp_map (
  -- E.g. https://idp.example.org/
  -- This is the same key as used in the metadata section
  idp_url VARCHAR(128) PRIMARY KEY,
  nren_id INTEGER NOT NULL,

  FOREIGN KEY(nren_id) REFERENCES nrens(nren_id) ON DELETE CASCADE
) engine=InnoDB;

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
    name VARCHAR(256) UNIQUE NOT NULL,
    -- the name that goes into the certificate subject DN (not that the
    -- organization name DN component may fill a maximum of 64 characters
    -- minus 'O=' that is 62 characters
    dn_name VARCHAR(62) UNIQUE NOT NULL,

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
    subscr_resp_email  VARCHAR(64) NOT NULL,
    subscr_comment TEXT DEFAULT "",

    -- Help-section.
    subscr_help_url VARCHAR(128) DEFAULT "",
    subscr_help_email VARCHAR(64) DEFAULT "",

    FOREIGN KEY(nren_id) REFERENCES nrens(nren_id) ON DELETE CASCADE
) engine=InnoDB;

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
	owner VARCHAR(256) NOT NULL,
	-- order number and collection code for bookkeeping, revocation,
	-- delivery
	order_date DATETIME NOT NULL,
	authorized ENUM('authorized', 'unauthorized', 'unknown') DEFAULT 'unknown',
	expires DATETIME NOT NULL
) engine=InnoDB;
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
       auth_key char(40) UNIQUE NOT NULL,
       -- the format of the CSR
       type ENUM('spkac','pkcs10') DEFAULT 'pkcs10'
) engine=InnoDB;

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
) engine=InnoDB;

-- admins
--
-- List of people having admin-rights on the page (to update news,
-- watch status of certificates etc)
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
       admin_id INT PRIMARY KEY AUTO_INCREMENT,
       admin varchar(256) NOT NULL, -- ePPN of the admin,

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
       -- store the idp with admins that are NREN admins but do not have an
       -- unique identifier that is unique within their NREN, but only within
       -- their IDP
       -- One can not use the subscriber for that, due to bootstrapping issues
       -- (the subscriber will not be bootstrapped in the beginning)
       idp_url VARCHAR(128),
       -- another nullable field, this time pointing to the NREN.
       -- This field helps to easily determine, for which NREN a NREN-admin is
       -- responsible. It would have been possible to find this information by
       -- joining over the subscriber to the NREN, but it would make bootstrapping
       -- hard, if the subscriber for each NREN-admin was to be known.
       -- The field can be left NULL or filled in if the admin is a subscriber
       -- admin.
       nren INT NOT NULL,
       -- a NREN-admin must have an unique identifier at least on the idp-level
       UNIQUE(admin, nren, idp_url),
       -- and a subscriber admin has an unique identifer at the subscriber level
       UNIQUE(admin, subscriber),
       FOREIGN KEY(subscriber) REFERENCES subscribers(subscriber_id) ON DELETE CASCADE,
       FOREIGN KEY(nren) REFERENCES nrens(nren_id) ON DELETE CASCADE
) engine=InnoDB;


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
       owner varchar(256), -- ePPN of the owner
       cert_sn INT NOT NULL,
       valid_untill DATETIME NOT NULL
) engine=InnoDB;

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
) engine=InnoDB;


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
       serial char(60) NOT NULL,
       fingerprint char(60) NOT NULL,

       -- Allow for a comment/description to be stored alongside the
       -- certificate.
       comment TEXT,

       FOREIGN KEY(subscriber_id) REFERENCES subscribers(subscriber_id) ON DELETE CASCADE,
       FOREIGN KEY(uploaded_by) REFERENCES admins(admin_id) ON DELETE CASCADE
) engine=InnoDB;

-- -----------------------------------------------------------------------------
-- critical_error
--
-- Store the errors regarded as critical in this table for making error-reporting
-- available to the outside of Confusa
CREATE TABLE IF NOT EXISTS critical_errors (
        errid       INT PRIMARY KEY AUTO_INCREMENT,
        error_date  DATETIME NOT NULL,
        /* more serious errors, higher number. Start a LOG_DEBUG = 0 */
        error_level INT NOT NULL,
        log_msg     TEXT NOT NULL,
        is_resolved BOOLEAN NOT NULL DEFAULT FALSE
) engine=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
