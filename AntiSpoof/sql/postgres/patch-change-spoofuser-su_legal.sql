-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: sql/abstractSchemaChanges/patch-change-spoofuser-su_legal.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
ALTER TABLE  spoofuser ALTER su_legal TYPE SMALLINT;
ALTER TABLE  spoofuser ALTER su_legal
DROP  DEFAULT;