-- This file is automatically generated using maintenance/generateSchemaChangeSql.php.
-- Source: repo/sql/abstractSchemaChanges/patch-wb_changes-change_object_id-index.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE INDEX change_object_id ON wb_changes (change_object_id);
