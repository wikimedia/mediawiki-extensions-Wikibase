-- Patch to add the term_search_key to wb_terms.
-- Licence: GNU GPL v2+


alter table /*_*/wb_terms add column term_search_key VARCHAR(255) NOT NULL;
alter table /*_*/wb_terms add index /*i*/wb_terms_search_key (term_search_key);
