-- Patch to add the term_lowercase_text to wb_terms.
-- Licence: GNU GPL v2+


alter table /*_*/wb_terms add column term_lowercase_text VARCHAR(255) NOT NULL;
alter table /*_*/wb_terms add index /*i*/wb_terms_lowercase_text (term_lowercase_text);
