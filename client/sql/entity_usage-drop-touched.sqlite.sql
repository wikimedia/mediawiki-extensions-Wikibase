-- get rid of the eu_touched column

-- Workaround for sqlite - T147300

BEGIN TRANSACTION;
CREATE TEMPORARY TABLE wbc_entity_usage_backup(eu_row_id,eu_entity_id,eu_aspect,eu_page_id);
INSERT INTO wbc_entity_usage_backup SELECT eu_row_id,eu_entity_id,eu_aspect,eu_page_id FROM wbc_entity_usage;
DROP TABLE wbc_entity_usage;
CREATE TABLE wbc_entity_usage(eu_row_id,eu_entity_id,eu_aspect,eu_page_id);
INSERT INTO wbc_entity_usage SELECT eu_row_id,eu_entity_id,eu_aspect,eu_page_id FROM wbc_entity_usage_backup;
DROP TABLE wbc_entity_usage_backup;
COMMIT;
