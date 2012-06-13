-- Patch to add the ll_local field to langlinks.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >

-- TODO: move to core and make matching changes to tables.sql files

ALTER TABLE /*_*/langlinks
	ADD COLUMN ll_local bool NOT NULL DEFAULT true;

