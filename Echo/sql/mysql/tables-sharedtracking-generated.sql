-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: sql/tables-sharedtracking.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/echo_unread_wikis (
  euw_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  euw_user INT UNSIGNED NOT NULL,
  euw_wiki VARCHAR(64) NOT NULL,
  euw_alerts INT UNSIGNED NOT NULL,
  euw_alerts_ts BINARY(14) NOT NULL,
  euw_messages INT UNSIGNED NOT NULL,
  euw_messages_ts BINARY(14) NOT NULL,
  UNIQUE INDEX echo_unread_wikis_user_wiki (euw_user, euw_wiki),
  PRIMARY KEY(euw_id)
) /*$wgDBTableOptions*/;
