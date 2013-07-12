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
  ips_row_id                 INT unsigned        NOT NULL PRIMARY KEY AUTO_INCREMENT, -- row ID
  ips_item_id                INT unsigned        NOT NULL, -- Id of the item
  ips_site_id                VARBINARY(32)       NOT NULL, -- Site identifier (global)
  ips_site_page              VARCHAR(255)        NOT NULL -- Title of the page
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_ips_item_site_page ON /*_*/wb_items_per_site (ips_site_id, ips_site_page);
CREATE INDEX /*i*/wb_ips_site_page ON /*_*/wb_items_per_site (ips_site_page);
CREATE INDEX /*i*/wb_ips_item_id ON /*_*/wb_items_per_site (ips_item_id);



-- Lookup table for entity terms (ie labels, aliases, descriptions).
CREATE TABLE IF NOT EXISTS /*_*/wb_terms (
  term_row_id                INT unsigned        NOT NULL PRIMARY KEY AUTO_INCREMENT, -- row ID
  term_entity_id             INT unsigned        NOT NULL, -- Id of the entity
  term_entity_type           VARBINARY(32)       NOT NULL, -- Type of the entity
  term_language              VARBINARY(32)       NOT NULL, -- Language code
  term_type                  VARBINARY(32)       NOT NULL, -- Term type
  term_text                  VARCHAR(255) binary NOT NULL, -- The term text
  term_search_key            VARCHAR(255) binary NOT NULL, -- The term text, lowercase for case-insensitive lookups
  term_weight                DOUBLE UNSIGNED     NOT NULL DEFAULT 0.0 -- weight for ranking
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_terms_entity_id ON /*_*/wb_terms (term_entity_id);
CREATE INDEX /*i*/wb_terms_entity_type ON /*_*/wb_terms (term_entity_type);
CREATE INDEX /*i*/wb_terms_language ON /*_*/wb_terms (term_language);
CREATE INDEX /*i*/wb_terms_type ON /*_*/wb_terms (term_type);
CREATE INDEX /*i*/wb_terms_text ON /*_*/wb_terms (term_text);
CREATE INDEX /*i*/wb_terms_search_key ON /*_*/wb_terms (term_search_key);

-- Links id+type to page ids.
CREATE TABLE IF NOT EXISTS /*_*/wb_entity_per_page (
  epp_entity_id                  INT unsigned        NOT NULL, -- Id of the entity
  epp_entity_type                VARBINARY(32)       NOT NULL, -- Type of the entity
  epp_page_id                    INT unsigned        NOT NULL -- Id of the page that store the entity
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_epp_entity ON /*_*/wb_entity_per_page (epp_entity_id, epp_entity_type);
CREATE UNIQUE INDEX /*i*/wb_epp_page ON /*_*/wb_entity_per_page (epp_page_id);
