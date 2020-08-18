-- Update wb_changes.change_info to MEDIUMBLOB - T108246

ALTER TABLE /*_*/wb_changes
MODIFY change_info MEDIUMBLOB NOT NULL;
