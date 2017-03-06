-- Adds a column to hold the full ID of the entity (a string, not only number part of the ID).

ALTER TABLE wb_terms
ADD COLUMN term_full_entity_id
VARCHAR(32) DEFAULT NULL
AFTER term_entity_id;

CREATE INDEX /*i*/term_full_entity ON /*_*/wb_terms (term_full_entity_id);
CREATE INDEX /*i*/term_search_full ON /*_*/wb_terms (term_language, term_full_entity_id, term_type, term_search_key(16));
