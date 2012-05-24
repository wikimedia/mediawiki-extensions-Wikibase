-- MySQL version of the database schema for the Wikibase extension.
-- Licence: GNU GPL v2+



-- Table holing a local copy of the items relevant to this wiki.
-- Also links the items to their associated pages with item_page_id.
CREATE TABLE IF NOT EXISTS /*_*/wbc_local_items (
  -- Foreign key on wb_items.item_id
  li_item_id                 INT unsigned        NOT NULL,

  -- Foreign key on page.page_id
  li_page_id                 INT unsigned        NOT NULL,

  -- Holds the actual \Wikibase\Item object in serialized PHP form.
  li_item_data               BLOB                NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wbc_li_item_id ON /*_*/wbc_local_items (li_item_id);
CREATE UNIQUE INDEX /*i*/wbc_li_page_id ON /*_*/wbc_local_items (li_page_id);