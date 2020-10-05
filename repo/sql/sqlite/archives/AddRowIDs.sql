-- Create unique row IDs, so we can efficiently update large tables later.

CREATE TABLE /*_*/wb_items_per_site_tmp (
  ips_row_id                 INT unsigned        NOT NULL PRIMARY KEY AUTO_INCREMENT, -- row ID
  ips_item_id                INT unsigned        NOT NULL, -- Id of the item
  ips_site_id                VARBINARY(32)       NOT NULL, -- Site identifier (global)
  ips_site_page              VARCHAR(255)        NOT NULL -- Title of the page
) /*$wgDBTableOptions*/;

INSERT INTO /*_*/wb_items_per_site_tmp( ips_item_id, ips_site_id, ips_site_page )
  SELECT ips_item_id, ips_site_id, ips_site_page FROM /*_*/wb_items_per_site;

DROP TABLE /*_*/wb_items_per_site;

ALTER TABLE /*_*/wb_items_per_site_tmp RENAME TO /*_*/wb_items_per_site;

CREATE UNIQUE INDEX IF NOT EXISTS /*i*/wb_ips_item_site_page ON /*_*/wb_items_per_site (ips_site_id, ips_site_page);
CREATE INDEX IF NOT EXISTS /*i*/wb_ips_site_page ON /*_*/wb_items_per_site (ips_site_page);
CREATE INDEX IF NOT EXISTS /*i*/wb_ips_item_id ON /*_*/wb_items_per_site (ips_item_id);
