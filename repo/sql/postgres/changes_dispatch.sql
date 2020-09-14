-- MySQL version of the database schema for the WikibaseLib extension.
-- Licence: GNU GPL v2+
-- Author: Daniel Kinzler

-- Change dispatch state
CREATE TABLE IF NOT EXISTS /*_*/wb_changes_dispatch (
  chd_site     TEXT         NOT NULL PRIMARY KEY,  -- client wiki's global site ID.
  chd_db       TEXT         NOT NULL,              -- client wiki's logical database name
  chd_seen     INTEGER      NOT NULL DEFAULT 0,    -- last change ID examined (dispatch state)
  chd_touched  TIMESTAMPTZ  NOT NULL DEFAULT '1970-01-01 00:00:00+00', -- end of last dispatch pass (informative)
  chd_lock     TEXT         DEFAULT NULL,          -- name of global lock (dispatch state)
  chd_disabled SMALLINT     NOT NULL DEFAULT 0     -- flag for temporarily disabling a client
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_changes_dispatch_chd_seen ON /*_*/wb_changes_dispatch (chd_seen);
CREATE INDEX /*i*/wb_changes_dispatch_chd_touched ON /*_*/wb_changes_dispatch (chd_touched);
