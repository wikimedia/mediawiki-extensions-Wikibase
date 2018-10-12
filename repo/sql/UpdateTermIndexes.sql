
DROP INDEX /*i*/wb_terms_entity_type ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_language ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_type ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_search_key ON /*_*/wb_terms;

-- Indexes and comments below adopted from the suggestions Sean Pringle made
-- at https://phabricator.wikimedia.org/T47529#518941 based on a
-- live analysis of queries on wikidata.org in January 2014.
-- NOTE: keep these in sync with Wikibase.sql

-- Same idea as above for terms_search_key (for normalized/insensitive matches).
CREATE INDEX /*i*/term_search_key ON /*_*/wb_terms (term_search_key, term_language);

