-- Patch to add the term_weight to wb_terms.
-- Licence: GNU GPL v2+

CREATE TABLE /*_*/wb_terms_tmp (
  term_entity_id             INT unsigned        NOT NULL, -- Id of the entity
  term_entity_type           VARBINARY(32)       NOT NULL, -- Type of the entity
  term_language              VARBINARY(32)       NOT NULL, -- Language code
  term_type                  VARBINARY(32)       NOT NULL, -- Term type
  term_text                  VARCHAR(255) binary NOT NULL, -- The term text
  term_search_key            VARCHAR(255) binary NOT NULL -- The term text, lowercase for case-insensitive lookups
  term_weight                DOUBLE UNSIGNED     NOT NULL DEFAULT 0.0 -- weight for ranking
) ) /*$wgDBTableOptions*/;

INSERT INTO /*_*/wb_terms_tmp( term_entity_id, term_entity_type, term_language, term_type, term_text, term_search_key, term_weight )
  SELECT term_entity_id, term_entity_type, term_language, term_type, term_text, term_text, term_search_key FROM wb_terms;

DROP TABLE /*_*/wb_terms;

ALTER TABLE /*_*/wb_terms_tmp RENAME TO /*_*/wb_terms;

CREATE INDEX IF NOT EXISTS /*i*/wb_terms_entity_id ON /*_*/wb_terms (term_entity_id);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_entity_type ON /*_*/wb_terms (term_entity_type);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_language ON /*_*/wb_terms (term_language);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_type ON /*_*/wb_terms (term_type);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_text ON /*_*/wb_terms (term_text);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_search_key ON /*_*/wb_terms (term_search_key);
