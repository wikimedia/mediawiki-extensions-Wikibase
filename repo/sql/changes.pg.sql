-- Postgres version of the database schema for the WikibaseLib extension.
-- Licence: GNU GPL v2+
-- Author: Jeroen De Dauw < jeroendedauw@gmail.com >

BEGIN;

CREATE SEQUENCE wb_changes_change_id_seq;

-- Change feed.
CREATE TABLE wb_changes (
  change_id                  INTEGER             NOT NULL PRIMARY KEY DEFAULT nextval('wb_changes_change_id_seq'), -- Id of change
  change_type                TEXT                NOT NULL, -- Type of the change
  change_time                TIMESTAMPTZ         NOT NULL, -- Time the change was made
  change_object_id           INTEGER             NOT NULL, -- The id of the object (ie item, query) the change affects
  change_revision_id         INTEGER             NOT NULL, -- The id of the revision on the repo that made the change
  change_user_id             INTEGER             NOT NULL, -- The id of the user on the repo that made the change
  change_info                TEXT                NOT NULL -- Holds additional info about the change, inc diff and stuff
);

CREATE INDEX /*i*/wb_changes_change_type ON wb_changes (change_type);
CREATE INDEX /*i*/wb_changes_change_time ON wb_changes (change_time);
CREATE INDEX /*i*/wb_changes_change_object_id ON wb_changes (change_object_id);
CREATE INDEX /*i*/wb_changes_change_user_id ON wb_changes (change_user_id);
CREATE INDEX /*i*/wb_changes_change_revision_id ON wb_changes (change_revision_id);

COMMIT;
