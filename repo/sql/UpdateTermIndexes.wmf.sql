-- This is a Wikimedia specific version of the update script for
-- the indexes on wb_terms table, as given in the terms.wmf.sql file.
-- It is optimized for very large data sets.

DROP INDEX /*i*/wb_terms_entity_id ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_entity_type ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_language ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_type ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_text ON /*_*/wb_terms;
DROP INDEX /*i*/wb_terms_search_key ON /*_*/wb_terms;

-- Indexes and comments below adopted from the suggestions Sean Pringle made
-- at https://bugzilla.wikimedia.org/show_bug.cgi?id=45529#c10 based on a
-- live analysis of queries on wikidata.org in January 2014.
-- NOTE: keep these in sync with Wikibase.sql


-- Partition by term_language. Need to re-define the primary key for this.
ALTER TABLE /*_*/wb_terms
MODIFY COLUMN term_row_id BIGINT unsigned NOT NULL;

DROP INDEX `PRIMARY` ON /*_*/wb_terms;

ALTER TABLE /*_*/wb_terms
ADD PRIMARY KEY (term_row_id, term_language);

ALTER TABLE /*_*/wb_terms
MODIFY COLUMN term_row_id BIGINT unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE /*_*/wb_terms
PARTITION BY KEY (term_language) PARTITIONS 16;


-- Some wb_terms queries use term_entity_id=N which is good selectivity.
CREATE INDEX /*i*/terms_entity ON /*_*/wb_terms (term_entity_id);

-- When any wb_terms query includes a search on term_text greater than
-- four or five leading characters a simple index on term_text and
-- language is often better than the proposed composite indexes. Note
-- that MariaDB still uses the entire key length even with LIKE '...%' on term_text.
CREATE INDEX /*i*/terms_text ON /*_*/wb_terms (term_text);

-- Same idea as above for terms_search_key (for normalized/insensitive matches).
CREATE INDEX /*i*/terms_search_key ON /*_*/wb_terms (term_search_key);

-- This index has good selectivity while still allowing ICP for short string values.
CREATE INDEX /*i*/terms_search ON /*_*/wb_terms (term_language, term_entity_id, term_type, term_search_key(16));

-- Note that term_language is kept in terms_search despite the partitions because
-- getMatchingIDs query still benefits from ICP when term_search_key is used with
-- a poorly performing short prefix: LIKE 'a%'.