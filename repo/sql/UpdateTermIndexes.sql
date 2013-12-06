
DROP INDEX /*i*/wb_terms_entity_id ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_entity_type ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_language ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_type ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_text ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_search_key ON /*_*/wb_terms;


-- for TermSqlIndex::getMatchingIDs
CREATE INDEX /*i*/term_search ON /*_*/wb_terms (term_language, term_search_key(12), term_entity_type, term_type, term_text);

-- for TermSqlIndex::getTermsOfEntity and for the join in TermSqlIndex::getMatchingTermCombination
CREATE INDEX /*i*/term_entity ON /*_*/wb_terms (term_entity_type, term_entity_id, term_type, term_text);

-- TermSqlIndex::getMatchingTerms with or without given term_text, as well as for TermSqlIndex::getMatchingTermCombination
CREATE UNIQUE INDEX /*i*/term_identity ON /*_*/wb_terms (term_language, term_type, term_entity_type, term_text, term_entity_id);
