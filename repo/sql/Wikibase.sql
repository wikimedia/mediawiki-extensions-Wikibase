-- MySQL version of the database schema for the Wikibase extension.
-- Licence: GNU GPL v2+


-- TODO: figure out which lenghts to use for some of the varchar fields.


-- Links items to articles
CREATE TABLE IF NOT EXISTS /*_*/wb_items (
  item_id                    INT unsigned        NOT NULL auto_increment PRIMARY KEY
  --item_page_id               INT unsigned        NOT NULL -- Foreign key on page.page_id
) /*$wgDBTableOptions*/;



-- Secondary storage.
-- Links site+title pairs to item ids.
CREATE TABLE IF NOT EXISTS /*_*/wb_items_per_site (
  ips_item_id                INT unsigned        NOT NULL, -- Id of the item
  ips_site_id                VARBINARY(32)       NOT NULL, -- Site identifier
  ips_site_page              VARCHAR(255)        NOT NULL -- Title of the page
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/ips_item_site_page ON /*_*/wb_items_per_site (ips_site_id, ips_site_page);
CREATE INDEX /*i*/ips_site_page ON /*_*/wb_items_per_site (ips_site_page);
CREATE INDEX /*i*/ips_item_id ON /*_*/wb_items_per_site (ips_item_id);



-- Secondary storage.
-- Holds internationalized texts (such as label and description)
CREATE TABLE IF NOT EXISTS /*_*/wb_texts_per_lang (
  tpl_item_id                INT unsigned        NOT NULL, -- Id of the item
  tpl_language               VARBINARY(32)       NOT NULL, -- Language code
  tpl_label                  VARCHAR(255)        NULL, -- Item label text
  tpl_description            VARCHAR(255)        NULL -- Item description text
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/tpl_item_id_lang ON /*_*/wb_texts_per_lang (tpl_item_id, tpl_language);
CREATE INDEX /*i*/tpl_language ON /*_*/wb_texts_per_lang (tpl_language);
CREATE INDEX /*i*/tpl_label ON /*_*/wb_texts_per_lang (tpl_label); -- TODO: might not be needed
