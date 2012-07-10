-- Postgres version of the database schema for the Wikibase extension.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >

BEGIN;

CREATE SEQUENCE wb_items_item_id_seq;

-- Links items to articles
CREATE TABLE wb_items (
  item_id                    INTEGER             PRIMARY KEY DEFAULT nextval('wb_items_item_id_seq')
);

-- Derived storage.
-- Links site+title pairs to item ids.
CREATE TABLE wb_items_per_site (
  ips_item_id                INTEGER             NOT NULL, -- Id of the item
  ips_site_id                TEXT                NOT NULL, -- Site identifier (global)
  ips_site_page              TEXT                NOT NULL -- Title of the page
);

CREATE UNIQUE INDEX /*i*/ips_item_site_page ON wb_items_per_site (ips_site_id, ips_site_page);
CREATE INDEX /*i*/ips_site_page ON wb_items_per_site (ips_site_page);
CREATE INDEX /*i*/ips_item_id ON wb_items_per_site (ips_item_id);



-- Derived storage.
-- Holds the aliases for the items.
CREATE TABLE wb_aliases (
  alias_item_id              INTEGER             NOT NULL, -- Id of the item
  alias_language             TEXT                NOT NULL, -- Language code
  alias_text                 TEXT                NOT NULL -- The alias text
);

CREATE UNIQUE INDEX /*i*/wb_aliases_unique ON wb_aliases (alias_item_id, alias_language, alias_text);
CREATE INDEX /*i*/wb_alias_item_id ON wb_aliases (alias_item_id);
CREATE INDEX /*i*/wb_alias_language ON wb_aliases (alias_language);
CREATE INDEX /*i*/wb_alias_text ON wb_aliases (alias_text);



-- Derived storage.
-- Holds internationalized texts (such as label and description)
CREATE TABLE wb_texts_per_lang (
  tpl_item_id                INTEGER             NOT NULL, -- Id of the item
  tpl_language               TEXT                NOT NULL, -- Language code
  tpl_label                  TEXT                NULL, -- Item label text
  tpl_description            TEXT                NULL -- Item description text
);

CREATE UNIQUE INDEX /*i*/tpl_item_id_lang ON wb_texts_per_lang (tpl_item_id, tpl_language);
CREATE INDEX /*i*/tpl_language ON wb_texts_per_lang (tpl_language);
CREATE INDEX /*i*/tpl_label ON wb_texts_per_lang (tpl_label); -- TODO: might not be needed


COMMIT;