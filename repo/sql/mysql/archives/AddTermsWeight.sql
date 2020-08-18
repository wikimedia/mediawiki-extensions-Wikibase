-- Patch to add the term_search_key to wb_terms.
-- Licence: GNU GPL v2+


alter table /*_*/wb_terms add column term_weight FLOAT UNSIGNED NOT NULL DEFAULT 0.0;
