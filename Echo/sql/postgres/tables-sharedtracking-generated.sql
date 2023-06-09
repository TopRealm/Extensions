-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: sql/tables-sharedtracking.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE echo_unread_wikis (
  euw_id SERIAL NOT NULL,
  euw_user INT NOT NULL,
  euw_wiki VARCHAR(64) NOT NULL,
  euw_alerts INT NOT NULL,
  euw_alerts_ts TIMESTAMPTZ NOT NULL,
  euw_messages INT NOT NULL,
  euw_messages_ts TIMESTAMPTZ NOT NULL,
  PRIMARY KEY(euw_id)
);

CREATE UNIQUE INDEX echo_unread_wikis_user_wiki ON echo_unread_wikis (euw_user, euw_wiki);
