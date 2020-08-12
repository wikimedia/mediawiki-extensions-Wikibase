-- MySQL version of the database schema for the WikibaseLib extension.
-- Licence: GNU GPL v2+
-- Author: Daniel Kinzler

-- Change dispatch state
CREATE TABLE IF NOT EXISTS /*_*/wb_changes_dispatch (
  chd_site     VARBINARY(32)     NOT NULL PRIMARY KEY,  -- client wiki's global site ID
  chd_db       VARBINARY(32)     NOT NULL,              -- client wiki's logical database name
  chd_seen     INT               NOT NULL DEFAULT 0,    -- last change ID examined (dispatch state)
  chd_touched  VARBINARY(14)     NOT NULL DEFAULT "00000000000000", -- end of last dispatch pass (informative)
  chd_lock     VARBINARY(64)              DEFAULT NULL, -- name of global lock (dispatch state)
  chd_disabled TINYINT UNSIGNED  NOT NULL DEFAULT 0     -- flag for temporarily disabling a client
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_changes_dispatch_chd_seen ON /*_*/wb_changes_dispatch (chd_seen);
CREATE INDEX /*i*/wb_changes_dispatch_chd_touched ON /*_*/wb_changes_dispatch (chd_touched);
