-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/Wikibase/repo/sql/abstract/wb_items_per_site.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/wb_items_per_site (
  ips_row_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  ips_item_id INTEGER UNSIGNED NOT NULL,
  ips_site_id BLOB NOT NULL,
  ips_site_page VARCHAR(310) NOT NULL
);

CREATE UNIQUE INDEX wb_ips_item_site_page ON /*_*/wb_items_per_site (ips_site_id, ips_site_page);

CREATE INDEX wb_ips_item_id ON /*_*/wb_items_per_site (ips_item_id);
