-- Migrates term_entity_id from integer to prefixed string.

-- NOTE: will only work if all indexes are dropped from wb_terms first!
-- See DropTermIndexes.sql resp. DropTermIndexes04.sql for that.

ALTER TABLE /*_*/wb_terms
ADD COLUMN term_entity_id_new VARBINARY(255) DEFAULT NULL;

UPDATE /*_*/wb_terms
SET term_entity_id_new = CONCAT( IF ( term_entity_type = 'property', 'P',
                                    IF ( term_entity_type = 'item', 'Q', NULL )  ),
                                 term_entity_id )
WHERE term_entity_id_new IS NULL;

ALTER TABLE /*_*/wb_terms
DROP COLUMN term_entity_id;

ALTER TABLE /*_*/wb_terms
CHANGE COLUMN term_entity_id_new term_entity_id VARBINARY(255) NOT NULL;

-- Don't forget to rebuild your indexes! See CreateTermIndexes.sql