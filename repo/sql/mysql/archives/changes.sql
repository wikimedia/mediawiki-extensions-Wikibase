-- MySQL version of the database schema for the WikibaseLib extension.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >


-- Change feed.
CREATE TABLE IF NOT EXISTS /*_*/wb_changes (
  change_id                  INT unsigned        NOT NULL PRIMARY KEY AUTO_INCREMENT, -- Id of change
  change_type                VARCHAR(25)         NOT NULL, -- Type of the change
  change_time                varbinary(14)       NOT NULL, -- Time the change was made
  change_object_id           varbinary(14)       NOT NULL, -- The full id of the object (ie item, query) the change affects
  change_revision_id         INT unsigned        NOT NULL, -- The id of the revision on the repo that made the change
  change_user_id             INT unsigned        NOT NULL, -- The id of the user on the repo that made the change
  change_info                MEDIUMBLOB          NOT NULL -- Holds additional info about the change, inc diff and stuff
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_changes_change_type ON /*_*/wb_changes (change_type);
CREATE INDEX /*i*/wb_changes_change_time ON /*_*/wb_changes (change_time);
CREATE INDEX /*i*/wb_changes_change_object_id ON /*_*/wb_changes (change_object_id);
CREATE INDEX /*i*/wb_changes_change_user_id ON /*_*/wb_changes (change_user_id);
CREATE INDEX /*i*/wb_changes_change_revision_id ON /*_*/wb_changes (change_revision_id);
