-- Add a column for tracking page.page_touched, to detect outdated entries in wbc_entity_usage.
ALTER TABLE /*_*/wbc_entity_usage
  ADD COLUMN eu_touched BINARY(14) NOT NULL DEFAULT '';
