-- Postgres version of the database schema for the WikibaseClient extension.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >

BEGIN;

CREATE SEQUENCE wbc_local_items_li_id_seq;

-- Table holing a local copy of the items relevant to this wiki.
-- Also links the items to their associated pages with item_page_id.
CREATE TABLE IF NOT EXISTS /*_*/wbc_local_items (
  li_id                      INTEGER             NOT NULL PRIMARY KEY DEFAULT nextval('wbc_local_items_li_id_seq'),

  -- Foreign key on wb_items.item_id
  li_item_id                 INTEGER             NOT NULL,

  -- Foreign key on page.page_title
  li_page_title              TEXT                NOT NULL,

  -- Holds the actual \Wikibase\Item object in serialized PHP form.
  li_item_data               TEXT                NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wbc_li_item_id ON /*_*/wbc_local_items (li_item_id);
CREATE UNIQUE INDEX /*i*/wbc_li_page_title ON /*_*/wbc_local_items (li_page_title);

COMMIT;