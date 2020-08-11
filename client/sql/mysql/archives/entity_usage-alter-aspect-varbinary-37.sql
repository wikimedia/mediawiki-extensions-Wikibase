-- Increase the width od the eu_aspect column to allow the inclusion of language codes in aspects.
ALTER TABLE /*_*/wbc_entity_usage
  MODIFY eu_aspect VARBINARY(37) NOT NULL;
