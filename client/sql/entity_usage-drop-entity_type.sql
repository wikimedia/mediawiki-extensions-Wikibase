-- get rid of the eu_entity_type column
DROP INDEX /*i*/eu_entity_type ON wbc_entity_usage;

ALTER TABLE /*_*/wbc_entity_usage
  DROP COLUMN eu_entity_type;
