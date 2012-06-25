-- Patch for the sites table.
-- Introduced in 0.1, can probably be removed once we are in production for a few weeks.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >


ALTER TABLE /*_*/sites
	DROP INDEX sites_allow_transclusion,
	DROP COLUMN site_allow_transclusion,
	ADD COLUMN site_language VARCHAR(10) NOT NULL DEFAULT 'en',
	ADD COLUMN site_data BLOB NOT NULL,
	ADD COLUMN site_config BLOB NOT NULL;

CREATE INDEX /*i*/sites_language ON /*_*/sites (site_language);