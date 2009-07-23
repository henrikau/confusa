-- ---------------------------------------------------------
--
-- Make cleaner way
--
-- ---------------------------------------------------------
CREATE OR REPLACE ALGORITHM = TEMPTABLE
VIEW nren_subscriber_view
	(subscriber,
	org_state,
	nren)
AS SELECT
	s.name,
	s.org_state,
	n.name 
FROM
	subscribers s
LEFT JOIN
	nrens n
ON
	s.nren_name = n.name;

CREATE OR REPLACE ALGORITHM = TEMPTABLE
VIEW nrens_account_map_view
	(nren_name, account_login_name)
AS SELECT
	nrens.name,
	nrens.login_name
FROM
	nrens
LEFT JOIN
	account_map
ON
	nrens.login_name = account_map.login_name;
