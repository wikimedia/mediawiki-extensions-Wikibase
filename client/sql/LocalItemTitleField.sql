-- Patch for the wbc_local_items table.
-- Introduced in 0.1, can probably be removed once we are in production for a few weeks.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >


ALTER TABLE /*_*/wbc_local_items
	DROP INDEX wbc_li_page_id,
	DROP COLUMN li_page_id,
	ADD COLUMN li_page_title VARCHAR(255) binary NOT NULL;

CREATE INDEX /*i*/wbc_li_page_title ON /*_*/wbc_local_items (li_page_title);