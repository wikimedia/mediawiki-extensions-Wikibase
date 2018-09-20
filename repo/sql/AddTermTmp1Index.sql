-- Add tmp1 Index on wb_terms according to T202265
CREATE INDEX IF NOT EXISTS /*i*/tmp1 ON /*_*/wb_terms (`term_language`,`term_type`,`term_entity_type`,`term_search_key`);
