-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: sql/discussiontools_subscription.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/discussiontools_subscription (
  sub_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  sub_item BLOB NOT NULL, sub_namespace INTEGER DEFAULT 0 NOT NULL,
  sub_title BLOB NOT NULL, sub_section BLOB NOT NULL,
  sub_state INTEGER DEFAULT 1 NOT NULL,
  sub_user INTEGER UNSIGNED NOT NULL,
  sub_created BLOB NOT NULL, sub_notified BLOB DEFAULT NULL
);

CREATE UNIQUE INDEX discussiontools_subscription_itemuser ON /*_*/discussiontools_subscription (sub_item, sub_user);

CREATE INDEX discussiontools_subscription_user ON /*_*/discussiontools_subscription (sub_user);
