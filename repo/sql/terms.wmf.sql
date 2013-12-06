-- This is a Wikimedia specific version of the definition of the wb_terms table
-- given in the generic Wikibase.sql file. It makes use of some advanced features
-- to accommodate very large data sets.

-- NOTE: keep this in sync with Wikibase.sql!
-- NOTE: keep this in sync with UpdateTermIndexes.wmf.sql!

-- Lookup table for entity terms (ie labels, aliases, descriptions),
-- partitioned by term_language.
CREATE TABLE IF NOT EXISTS /*_*/wb_terms (
  term_row_id                BIGINT unsigned     NOT NULL AUTO_INCREMENT, -- row ID
  term_entity_id             INT unsigned        NOT NULL, -- Id of the entity
  term_entity_type           VARBINARY(32)       NOT NULL, -- Type of the entity
  term_language              VARBINARY(32)       NOT NULL, -- Language code
  term_type                  VARBINARY(32)       NOT NULL, -- Term type
  term_text                  VARCHAR(255) binary NOT NULL, -- The term text
  term_search_key            VARCHAR(255) binary NOT NULL, -- The term text, lowercase for case-insensitive lookups
  term_weight                FLOAT UNSIGNED      NOT NULL DEFAULT 0.0, -- weight for ranking
  PRIMARY KEY (term_row_id, term_language),
  KEY term_entity (term_entity_id),
  KEY term_text (term_text),
  KEY term_search_key (term_search_key),
  KEY term_search (term_language,term_entity_type,term_type,term_search_key(16))
) ENGINE=InnoDB PARTITION BY KEY (term_language) PARTITIONS 16;

-- Note that term_language is kept in terms_search despite the partitions because
-- getMatchingIDs query still benefits from ICP when term_search_key is used with
-- a poorly performing short prefix: LIKE 'a%'.