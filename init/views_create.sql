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
	subscriber_dn,
	org_state,
	nren,
	nren_id)
AS SELECT
	s.subscriber_id,
	s.name,
	s.dn_name,
	s.org_state,
	n.name,
	n.nren_id
FROM
	subscribers s
LEFT JOIN
	nrens n
ON
	s.nren_id = n.nren_id;
