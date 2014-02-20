-- Migrates from term_entity_id that holds an int to term_full_entity_id that holds the full id.

-- NOTE: will only work if all indexes are dropped from wb_terms first!
-- See DropTermIndexes.sql resp. DropTermIndexes04.sql for that.

ALTER TABLE /*_*/wb_terms
ADD COLUMN term_full_entity_id VARBINARY(255) DEFAULT NULL;

UPDATE /*_*/wb_terms
SET term_full_entity_id = CASE WHEN term_entity_type = 'property' THEN 'P' ELSE 'Q' END || term_entity_id
WHERE term_full_entity_id IS NULL;

-- Don't forget to rebuild your indexes! See CreateTermIndexes.sql