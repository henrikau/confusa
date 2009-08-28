-- ---------------------------------------------------------
--
-- Create views in order not having to join every time across frequently
-- combined tables in Confusa
--
-- ---------------------------------------------------------
CREATE OR REPLACE ALGORITHM = TEMPTABLE
VIEW nren_subscriber_view
	(subscriber_id,
	subscriber,
	org_state,
	nren)
AS SELECT
	s.subscriber_id,
	s.name,
	s.org_state,
	n.name
FROM
	subscribers s
LEFT JOIN
	nrens n
ON
	s.nren_id = n.nren_id;

CREATE OR REPLACE ALGORITHM = TEMPTABLE
VIEW nren_account_map_view
	(nren, account_login_name, account_password, account_ivector, ap_name)
AS SELECT
	n.name,
	a.login_name,
	a.password,
	a.ivector,
	a.ap_name
FROM
	nrens n
LEFT JOIN
	account_map a
ON
	n.login_account = a.account_map_id;
