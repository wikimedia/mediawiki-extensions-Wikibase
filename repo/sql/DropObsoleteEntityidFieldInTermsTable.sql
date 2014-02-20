ALTER TABLE /*_*/wb_terms
DROP COLUMN term_entity_id;

ALTER TABLE /*_*/wb_terms
CHANGE COLUMN term_entity_id_new term_entity_id VARBINARY(255) NOT NULL;