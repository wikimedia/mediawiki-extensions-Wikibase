-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/Wikibase/repo/sql/abstract/wb_items_per_site.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE wb_items_per_site (
  ips_row_id BIGSERIAL NOT NULL,
  ips_item_id INT NOT NULL,
  ips_site_id TEXT NOT NULL,
  ips_site_page VARCHAR(310) NOT NULL,
  PRIMARY KEY(ips_row_id)
);

CREATE UNIQUE INDEX wb_ips_item_site_page ON wb_items_per_site (ips_site_id, ips_site_page);

CREATE INDEX wb_ips_item_id ON wb_items_per_site (ips_item_id);
