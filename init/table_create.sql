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
-- account_map
--
-- Map an account with the Online CA provider.
-- Such an account currently consists of a username and a password.
--
-- The password is stored in encrypted form, but the encryption should take
-- place in the PHP application, because of flaws in MySQL's AES_ENCRYPT
-- (see http://moncahier.canalblog.com/archives/2008/01/26/7700105.html)
-- and because it is safer if the password is encrypted as early as possible.
-- PHP::MCrypt also provides us with more options (block mode, etc.) and makes
-- it easier to change to different encryption algorithms.
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS account_map (
    -- the login-name for the associated sub-account,
    login_name VARCHAR(128) PRIMARY KEY NOT NULL,
    -- the password with which the sub-account will be accessed.
    -- encrypted at application layer
    password TINYBLOB NOT NULL,
    -- the initialization vector used for encryption
    -- the vector must be random, but need not be confidential
    ivector TINYBLOB NOT NULL
) type=InnoDB;

-- ---------------------------------------------------------
--
-- nrens
--
-- Store the NRENs that are currently hooked up to Confusa
-- If Confusa operates in remote-signing mode, it will also use the linked
-- online account for requesting certificates for an organization.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS nrens (
    -- the name of the NREN (e.g. SUNET, UNINETT, FUNET)
    name VARCHAR(30) PRIMARY KEY NOT NULL,
    -- if a remote signing CA is used, the ID of the subaccont there
    login_name VARCHAR(128),
    FOREIGN KEY(login_name) REFERENCES account_map(login_name) ON DELETE SET NULL
) type=InnoDB;

-- ---------------------------------------------------------
--
-- institutions
--
-- Store the organizations that are currently hooked up to Confusa along with
-- their current state (subscribed, suspended, unsubscribed)
--
-- If Confusa operates in remote-signing mode, it will also use the linked
-- online account for requesting certificates for an organization.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS institutions (
    inst_id INT PRIMARY KEY AUTO_INCREMENT,
    -- the name of the institution (e.g. KTH, CSC, Univ. of Oslo,...)
    name VARCHAR(30) NOT NULL,
    -- the NREN as it is stored in the NREN table
    nren_name VARCHAR(30) NOT NULL,
    -- the current subscription state to the service
    org_state ENUM('subscribed', 'suspended', 'unsubscribed') NOT NULL,
    FOREIGN KEY(nren_name) REFERENCES nrens(name) ON DELETE CASCADE
) type=InnoDB;

-- ---------------------------------------------------------
--
-- cert_user
--
-- Table that holds the common_name and institution of users iff they still
-- possess non-expired certificates or CSRs
-- Certificate and CSR tables will have foreign keys pointing to this table,
-- so all tables will be forced to a consistent representation of the users'
-- common_names
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS cert_user (
    common_name VARCHAR(128) PRIMARY KEY NOT NULL,
    institution VARCHAR(30) NOT NULL,
    expires DATETIME NOT NULL
) type=InnoDB;

-- ---------------------------------------------------------
--
-- order_store
--
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
	order_number INT PRIMARY KEY NOT NULL,
	-- auth_key and owner for remote download and upload
	auth_key CHAR(64) NOT NULL,
	common_name VARCHAR(128) NOT NULL,
	-- order number and collection code for bookkeeping, revocation,
	-- delivery
	order_date DATETIME NOT NULL,
	authorized ENUM('authorized', 'unauthorized', 'unknown') DEFAULT 'unknown',
	expires DATETIME NOT NULL,
    FOREIGN KEY(common_name) REFERENCES cert_user(common_name) ON DELETE CASCADE
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
	-- the organization of the cert_owner. Useful for querying for certificates
	-- to-be-revoked e.g. when operating in stand-alone mode
	organization varchar(64) NOT NULL,
	valid_untill DATETIME NOT NULL
) type=InnoDB;

-- ---------------------------------------------------------
--
-- user_crls
--
-- Holds the list of serial-numbers for issued certificates
-- so that the user may revoke them when needed.
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_crls(
    crl_id INT PRIMARY KEY AUTO_INCREMENT,
    common_name VARCHAR(128) NOT NULL,
    cert_sn INT NOT NULL,
    valid_untill DATETIME NOT NULL,
    FOREIGN KEY(common_name) REFERENCES cert_user(common_name) ON DELETE CASCADE
) type=InnoDB;

-- ---------------------------------------------------------
-- admins
--
-- List of people having admin-rights on the page (to update news,
-- watch status of certificates etc)
--
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
       admin varchar(128) PRIMARY KEY, -- common_name of the admin,
       -- The level of admin privileges
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
       last_mode ENUM('0','1') DEFAULT 0
) type=InnoDB;
<<<<<<< HEAD:init/table_create.sql
=======

 
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
>>>>>>> fw:init/table_create.sql
