-- Adds a column to hold the full ID of the entity (a string, not only number part of the ID).
-- In the long-run term_entity_id_s is going to replace term_entity_id.

ALTER TABLE wb_terms
ADD COLUMN term_entity_id_s
VARCHAR(32) DEFAULT NULL
AFTER term_entity_id;

-- Once term_entity_id_s is populated it should become NOT NULL and indices should be created:

--CREATE INDEX /*i*/term_entity_s ON /*_*/wb_terms (term_entity_id_s);

--CREATE INDEX /*i*/term_search_s ON /*_*/wb_terms (term_language, term_entity_id_s, term_type, term_search_key(16));