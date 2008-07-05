-- ---------------------------------------------------------
-- 
-- This is the table for the SCLS-certificate.
--
-- This is where the signing routine will store the signed CSR.
-- SLCS-web should send the CSR directly to the signing-routine,
-- and check in later to see if a signed CSR has arrived. 
-- 
-- ---------------------------------------------------------
DROP TABLE IF EXISTS slcs_certs;

CREATE TABLE slcs_certs (
       -- id of the user
       -- This is used for passing the argument to and from scripts. As it is easier to
       -- just add data in defined points, all communcation between processes should be done
       -- via the id of the CSR
       id int not null primary key auto_increment,

       -- id of the user, in the form of the common-name (eduPersonPrincipleName from Feide)
       -- Connect it to the sms_auth table.
       common_name varchar(128) not null,

       -- the CSR
       csr blob,

       -- wether or not the CSR is signed
       signed ENUM('new', 'signed', 'error') default 'error',
       -- how long the certificate is valid. This is stored here in case the user
       -- creates the certificate, but the signing takes a very long time and the users decides
       -- to return later.
       -- How long it should be 'valid' in the database, is a matter of debate
       valid_untill DATETIME default '0000-00-00 00:00:00'
) type=InnoDB;
