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
  ips_site_page              VARCHAR(310)        NOT NULL -- Prefixed title of the page
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_ips_item_site_page ON /*_*/wb_items_per_site (ips_site_id, ips_site_page);
CREATE INDEX /*i*/wb_ips_site_page ON /*_*/wb_items_per_site (ips_site_page);
CREATE INDEX /*i*/wb_ips_item_id ON /*_*/wb_items_per_site (ips_item_id);



-- Lookup table for entity terms (ie labels, aliases, descriptions).
-- NOTE: keep the Wikimedia specific terms.wmf.sql in sync with this!
CREATE TABLE IF NOT EXISTS /*_*/wb_terms (
  term_row_id                BIGINT unsigned     NOT NULL PRIMARY KEY AUTO_INCREMENT, -- row ID
  term_entity_id             INT unsigned        NOT NULL, -- Id of the entity
  term_full_entity_id        VARBINARY(32)       DEFAULT NULL, -- Full id of the entity (not only numeric part)
  term_entity_type           VARBINARY(32)       NOT NULL, -- Type of the entity
  term_language              VARBINARY(32)       NOT NULL, -- Language code
  term_type                  VARBINARY(32)       NOT NULL, -- Term type
  term_text                  VARCHAR(255) binary NOT NULL, -- The term text
  term_search_key            VARCHAR(255) binary NOT NULL, -- The term text, lowercase for case-insensitive lookups
  term_weight                FLOAT UNSIGNED     NOT NULL DEFAULT 0.0 -- weight for ranking
) /*$wgDBTableOptions*/;

-- Indexes and comments below adopted from the suggestions Sean Pringle made
-- at https://phabricator.wikimedia.org/T47529#518941 based on a
-- live analysis of queries on wikidata.org in January 2014.
-- NOTE: keep these in sync with UpdateTermIndexes.sql

-- Some wb_terms queries use term_entity_id=N which is good selectivity.
CREATE INDEX /*i*/term_entity ON /*_*/wb_terms (term_entity_id);

-- Some wb_terms queries use term_entity_id_s=X which is good selectivity.
CREATE INDEX /*i*/term_full_entity ON /*_*/wb_terms (term_full_entity_id);

-- When any wb_terms query includes a search on term_text greater than
-- four or five leading characters a simple index on term_text and
-- language is often better than the proposed composite indexes. Note
-- that MariaDB still uses the entire key length even with LIKE '...%' on term_text.
CREATE INDEX /*i*/term_text ON /*_*/wb_terms (term_text, term_language);

-- Same idea as above for terms_search_key (for normalized/insensitive matches).
CREATE INDEX /*i*/term_search_key ON /*_*/wb_terms (term_search_key, term_language);

-- This index has good selectivity while still allowing ICP for short string values.
CREATE INDEX /*i*/term_search ON /*_*/wb_terms (term_language, term_entity_id, term_type, term_search_key(16));

-- This index has good selectivity while still allowing ICP for short string values.
CREATE INDEX /*i*/term_search_full ON /*_*/wb_terms (term_language, term_full_entity_id, term_type, term_search_key(16));

