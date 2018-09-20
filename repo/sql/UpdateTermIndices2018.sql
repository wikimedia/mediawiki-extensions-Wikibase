-- Add wb_term_text according to T204837
CREATE INDEX IF NOT EXISTS /*i*/wb_term_text ON /*_*/wb_terms (`term_text`);

-- Add wb_term_search_key according to T204838
CREATE INDEX IF NOT EXISTS /*i*/wb_term_search_key ON /*_*/wb_terms (`term_search_key`);

-- Add wb_terms_entity_id according to T204836
CREATE INDEX IF NOT EXISTS /*i*/wb_terms_entity_id ON /*_*/wb_terms (`term_entity_id`);
