-- MySQL version of the database schema for the Wikibase extension.
-- Licence: GNU GPL v2+


-- TODO: figure out which lengths to use for some of the varchar fields.


-- Unique ID generator.
CREATE TABLE IF NOT EXISTS /*_*/wb_id_counters (
  id_value                   INT unsigned        NOT NULL,
  id_type                    VARBINARY(32)       NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_id_counters_type ON /*_*/wb_id_counters (id_type);



-- Derived storage.
-- Links site+title pairs to item ids.
CREATE TABLE IF NOT EXISTS /*_*/wb_items_per_site (
  ips_row_id                 BIGINT unsigned     NOT NULL PRIMARY KEY AUTO_INCREMENT, -- row ID
  ips_item_id                INT unsigned        NOT NULL, -- Id of the item
  ips_site_id                VARBINARY(32)       NOT NULL, -- Site identifier (global)
  ips_site_page              VARCHAR(255)        NOT NULL -- Title of the page
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_ips_item_site_page ON /*_*/wb_items_per_site (ips_site_id, ips_site_page);
CREATE INDEX /*i*/wb_ips_site_page ON /*_*/wb_items_per_site (ips_site_page);
CREATE INDEX /*i*/wb_ips_item_id ON /*_*/wb_items_per_site (ips_item_id);



-- Lookup table for entity terms (ie labels, aliases, descriptions).
CREATE TABLE IF NOT EXISTS /*_*/wb_terms (
  term_row_id                BIGINT unsigned     NOT NULL PRIMARY KEY AUTO_INCREMENT, -- row ID
  term_entity_id             INT unsigned        NOT NULL, -- Id of the entity
  term_entity_type           VARBINARY(32)       NOT NULL, -- Type of the entity
  term_language              VARBINARY(32)       NOT NULL, -- Language code
  term_type                  VARBINARY(32)       NOT NULL, -- Term type
  term_text                  VARCHAR(255) binary NOT NULL, -- The term text
  term_search_key            VARCHAR(255) binary NOT NULL, -- The term text, lowercase for case-insensitive lookups
  term_weight                FLOAT UNSIGNED     NOT NULL DEFAULT 0.0 -- weight for ranking
) /*$wgDBTableOptions*/;

-- for TermSqlIndex::getMatchingIDs
CREATE INDEX /*i*/term_search ON /*_*/wb_terms (term_language, term_search_key(12), term_entity_type, term_type, term_text);

-- for TermSqlIndex::getTermsOfEntity and for the join in TermSqlIndex::getMatchingTermCombination
CREATE INDEX /*i*/term_entity ON /*_*/wb_terms (term_entity_type, term_entity_id, term_type, term_text);

-- TermSqlIndex::getMatchingTerms with or without given term_text, as well as for TermSqlIndex::getMatchingTermCombination
CREATE UNIQUE INDEX /*i*/term_identity ON /*_*/wb_terms (term_language, term_type, term_entity_type, term_text, term_entity_id);

-- Links id+type to page ids.
CREATE TABLE IF NOT EXISTS /*_*/wb_entity_per_page (
  epp_entity_id                  INT unsigned        NOT NULL, -- Id of the entity
  epp_entity_type                VARBINARY(32)       NOT NULL, -- Type of the entity
  epp_page_id                    INT unsigned        NOT NULL -- Id of the page that store the entity
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_epp_entity ON /*_*/wb_entity_per_page (epp_entity_id, epp_entity_type);
CREATE UNIQUE INDEX /*i*/wb_epp_page ON /*_*/wb_entity_per_page (epp_page_id);
