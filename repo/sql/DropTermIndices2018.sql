--remove term_text according to T204837
DROP INDEX IF EXISTS /*i*/term_text ON /*_*/wb_terms;
-- remove_term_search_key according to T204838
DROP INDEX IF EXISTS /*i*/term_search_key ON /*_*/wb_terms;
