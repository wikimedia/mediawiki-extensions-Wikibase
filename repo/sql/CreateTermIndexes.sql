-- creates indexes for wb_terms

-- for TermSqlIndex::getMatchingIDs
CREATE INDEX /*i*/term_search ON /*_*/wb_terms (term_language(32), term_search_key(12), term_entity_type(32), term_type(32), term_text);

-- for TermSqlIndex::getTermsOfEntity and for the join in TermSqlIndex::getMatchingTermCombination
CREATE INDEX /*i*/term_entity ON /*_*/wb_terms (term_entity_id, term_type(32), term_language(32), term_text);

-- TermSqlIndex::getMatchingTerms with or without given term_text, as well as for TermSqlIndex::getMatchingTermCombination
CREATE UNIQUE INDEX /*i*/term_identity ON /*_*/wb_terms (term_language(32), term_type(32), term_entity_type(32), term_text, term_entity_id);
