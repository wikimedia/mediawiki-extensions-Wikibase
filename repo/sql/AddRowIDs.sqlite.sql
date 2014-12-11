-- Create unique row IDs, so we can efficiently update large tables later.

CREATE TABLE /*_*/wb_items_per_site_tmp (
  ips_row_id                 INT unsigned        NOT NULL PRIMARY KEY AUTO_INCREMENT, -- row ID
  ips_item_id                INT unsigned        NOT NULL, -- Id of the item
  ips_site_id                VARBINARY(32)       NOT NULL, -- Site identifier (global)
  ips_site_page              VARCHAR(255)        NOT NULL -- Title of the page
) /*$wgDBTableOptions*/;

INSERT INTO /*_*/wb_items_per_site_tmp( ips_item_id, ips_site_id, ips_site_page )
  SELECT ips_item_id, ips_site_id, ips_site_page FROM wb_items_per_site;

DROP TABLE /*_*/wb_items_per_site;

ALTER TABLE /*_*/wb_items_per_site_tmp RENAME TO /*_*/wb_items_per_site;

CREATE UNIQUE INDEX IF NOT EXISTS /*i*/wb_ips_item_site_page ON /*_*/wb_items_per_site (ips_site_id, ips_site_page);
CREATE INDEX IF NOT EXISTS /*i*/wb_ips_site_page ON /*_*/wb_items_per_site (ips_site_page);
CREATE INDEX IF NOT EXISTS /*i*/wb_ips_item_id ON /*_*/wb_items_per_site (ips_item_id);

CREATE TABLE /*_*/wb_terms_tmp (
  term_row_id                INT unsigned        NOT NULL PRIMARY KEY AUTO_INCREMENT, -- row ID
  term_entity_id             INT unsigned        NOT NULL, -- Id of the entity
  term_entity_type           VARBINARY(32)       NOT NULL, -- Type of the entity
  term_language              VARBINARY(32)       NOT NULL, -- Language code
  term_type                  VARBINARY(32)       NOT NULL, -- Term type
  term_text                  VARCHAR(255) binary NOT NULL, -- The term text
  term_search_key            VARCHAR(255) binary NOT NULL -- The term text, lowercase for case-insensitive lookups
) /*$wgDBTableOptions*/;

INSERT INTO /*_*/wb_terms_tmp( term_entity_id, term_entity_type, term_language, term_type, term_text, term_search_key )
  SELECT term_entity_id, term_entity_type, term_language, term_type, term_text, term_search_key FROM wb_terms;

DROP TABLE /*_*/wb_terms;

ALTER TABLE /*_*/wb_terms_tmp RENAME TO /*_*/wb_terms;

CREATE INDEX IF NOT EXISTS /*i*/wb_terms_entity_id ON /*_*/wb_terms (term_entity_id);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_entity_type ON /*_*/wb_terms (term_entity_type);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_language ON /*_*/wb_terms (term_language);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_type ON /*_*/wb_terms (term_type);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_text ON /*_*/wb_terms (term_text);
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_search_key ON /*_*/wb_terms (term_search_key);
