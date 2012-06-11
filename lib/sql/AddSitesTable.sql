-- Patch to add the sites table.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >

-- TODO: move to core and make matching changes to tables.sql files

-- Holds all the sites known to the wiki.
-- This includes their associated data and handling configuration.
-- In case Wikibase is used, several fields are obtained from the repo.
CREATE TABLE IF NOT EXISTS /*_*/sites (
-- Numeric id of the site
  site_id                    INT UNSIGNED        NOT NULL PRIMARY KEY AUTO_INCREMENT,

  -- Global identifier for the site, ie enwiktionary
  site_global_key            VARCHAR(25)         NOT NULL, -- obtained from repo

  -- Type of the site, ie SITE_MW
  site_type                  INT UNSIGNED        NOT NULL, -- obtained from repo

  -- Group of the site, ie SITE_GROUP_WIKIPEDIA
  site_group                 INT UNSIGNED        NOT NULL, -- obtained from repo

  -- Base URL of the site, ie http://en.wikipedia.org
  site_url                   VARCHAR(255)        NOT NULL, -- obtained from repo

  -- Path of pages relative to the base url, ie /wiki/$1
  site_page_path             VARCHAR(255)        NOT NULL, -- obtained from repo

  -- Path of files relative to the base url, ie /w/
  site_file_path             VARCHAR(255)        NOT NULL, -- obtained from repo

  -- Local identifier for the site, ie en
  site_local_key             VARCHAR(25)         NOT NULL,

  -- If the site should be linkable inline as an "interwiki link" using
  -- [[site_global_key:pageTitle]] or [[site_local_key:pageTitle]].
  site_link_inline           bool                NOT NULL,

  -- If equivalent pages of this site should be listed.
  -- For example in the "language links" section.
  site_link_equivalents      bool                NOT NULL,

  -- If site.tld/path/key:pageTitle should forward users to  the page on
  -- the actual site, where "key" os either the local or global identifier.
  site_forward               bool                NOT NULL,

  -- If template translcusion should be allowed or not.
  -- TODO: if we need to search against this, then it probably should
  -- go in it's own table as this is MW specific. If we don't need
  -- to search against it, then we can create a site_info blob
  -- that holds it and possibly other misc stuff.
  site_allow_transclusion    bool                NOT NULL
) /*$wgDBTableOptions*/;