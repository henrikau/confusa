-- ---------------------------------------------------------------------------
-- Create triggers to enforce constraints on tables that are not easily
-- enforceable in another way
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- Need a different delimiter for a trigger, otherwise MySQL will interpret
-- that the block ends at ';'.
-- ---------------------------------------------------------------------------
delimiter $$

DROP TRIGGER IF EXISTS checkAdminAffiliation;
DROP TRIGGER IF EXISTS checkAdminAffiliationU;

-- ---------------------------------------------------------------------------
-- A NREN admin should not be affiliated with a subscriber, only with a NREN
-- A subscriber admin or subscriber subadmin should not be affiliated with a
-- NREN. That trigger enforces that
-- --------------------------------------------------------------------------
CREATE TRIGGER checkAdminAffiliation
BEFORE INSERT ON admins
FOR EACH ROW
	BEGIN
		IF NEW.admin_level = '2' THEN
			SET NEW.subscriber=NULL;
		ELSE
			SET NEW.nren=NULL;
		END IF;
	END$$

-- ---------------------------------------------------------------------------
-- MySQL can not create a trigger on INSERT OR UPDATE. Hence, create another
-- one for updates
-- ---------------------------------------------------------------------------
CREATE TRIGGER checkAdminAffiliationU
BEFORE UPDATE ON admins
FOR EACH ROW
	BEGIN
		IF NEW.admin_level = '2' THEN
			SET NEW.subscriber=NULL;
		ELSE
			SET NEW.nren=NULL;
		END IF;
	END$$
