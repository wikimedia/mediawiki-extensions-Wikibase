-- MySQL version of the database schema for the Wikibase Client extension.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >

-- Table for usage lookups of items.
CREATE TABLE IF NOT EXISTS /*_*/wbc_item_usage (
  -- Foreign key on wbc_entity_cache.ec_entity_id
  iu_item_id                 INT unsigned        NOT NULL,

  -- Foreign key on page.page_id
  iu_page_id                 INT unsigned        NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wbc_iu_item_id ON /*_*/wbc_item_usage (iu_item_id);
CREATE INDEX /*i*/wbc_iu_page_id ON /*_*/wbc_item_usage (iu_page_id);


-- Table for usage lookups of queries.
CREATE TABLE IF NOT EXISTS /*_*/wbc_query_usage (
  -- Foreign key on wbc_entity_cache.ec_entity_id
  qu_query_id                INT unsigned        NOT NULL,

  -- Foreign key on page.page_id
  qu_page_id                 INT unsigned        NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wbc_qu_query_id ON /*_*/wbc_query_usage (qu_query_id);
CREATE INDEX /*i*/wbc_qu_page_id ON /*_*/wbc_query_usage (qu_page_id);