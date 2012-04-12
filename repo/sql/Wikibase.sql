-- MySQL version of the database schema for the Wikibase extension.
-- Licence: GNU GPL v2+


-- TODO: figure out which lenghts to use for some of the varchar fields.


-- Secondary storage.
-- Links wiki+title pairs to item ids.
CREATE TABLE IF NOT EXISTS /*_*/wb_items_per_wiki (
  ipw_item_id                INT unsigned        NOT NULL, -- Id of the item
  ipw_wiki_id                VARCHAR(255)        NOT NULL, -- Wiki identifier
  ipw_wiki_page              VARCHAR(255)        NOT NULL, -- Title of the page
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/ipw_item_wiki_page ON /*_*/wb_items_per_wiki (ipw_wiki_id, ipw_wiki_page);
CREATE INDEX /*i*/ipw_wiki_page ON /*_*/wb_items_per_wiki (ipw_wiki_page);
CREATE INDEX /*i*/ipw_item_id ON /*_*/wb_items_per_wiki (ipw_item_id);


-- Secondary storage.
-- Holds internationalized texts (such as label and description)
CREATE TABLE IF NOT EXISTS /*_*/wb_texts_per_lang (
  tpl_item_id                INT unsigned        NOT NULL, -- Id of the item
  tpl_language               VARCHAR(255)        NOT NULL, -- Language code
  tpl_label                  VARCHAR(255)        NOT NULL, -- Item label text
  tpl_description            VARCHAR(255)        NOT NULL, -- Item description text
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/tpl_item_id_language ON /*_*/wb_texts_per_lang (tpl_item_id, tpl_language);
CREATE INDEX /*i*/tpl_language ON /*_*/wb_texts_per_lang (tpl_language);
CREATE INDEX /*i*/tpl_label ON /*_*/wb_texts_per_lang (tpl_label); -- TODO: might not be needed
