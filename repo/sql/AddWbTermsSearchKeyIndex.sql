DROP INDEX /*i*/term_search_key ON /*_*/wb_terms;

CREATE INDEX /*i*/wb_terms_search_key ON /*_*/wb_terms (term_search_key);
