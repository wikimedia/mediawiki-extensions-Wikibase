-- get rid of the eu_touched column

ALTER TABLE /*_*/wbc_entity_usage
  DROP COLUMN eu_touched;
