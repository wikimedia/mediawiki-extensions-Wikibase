DROP INDEX /*i*/term_text ON /*_*/wb_terms;

CREATE INDEX /*i*/wb_terms_text ON /*_*/wb_terms (term_text);
