
DROP INDEX /*i*/wb_terms_entity_type ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_language ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_type ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_text ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_search_key ON /*_*/wb_terms;

-- Indexes and comments below adopted from the suggestions Sean Pringle made
-- at https://phabricator.wikimedia.org/T47529#518941 based on a
-- live analysis of queries on wikidata.org in January 2014.
-- NOTE: keep these in sync with Wikibase.sql

-- When any wb_terms query includes a search on term_text greater than
-- four or five leading characters a simple index on term_text and
-- language is often better than the proposed composite indexes. Note
-- that MariaDB still uses the entire key length even with LIKE '...%' on term_text.
CREATE INDEX /*i*/term_text ON /*_*/wb_terms (term_text, term_language);

-- Same idea as above for terms_search_key (for normalized/insensitive matches).
CREATE INDEX /*i*/term_search_key ON /*_*/wb_terms (term_search_key, term_language);

