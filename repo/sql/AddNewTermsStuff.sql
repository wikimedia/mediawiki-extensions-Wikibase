CREATE TABLE IF NOT EXISTS /*_*/wb_item_terms (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  item_id                         INT unsigned       NOT NULL,
  term_in_lang_id                 INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_item_terms_item_id ON /*_*/wb_item_terms (item_id);
CREATE INDEX /*i*/wb_item_terms_term_in_lang_id ON /*_*/wb_item_terms (term_in_lang_id);

CREATE TABLE IF NOT EXISTS /*_*/wb_property_terms (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  property_id                     INT unsigned       NOT NULL,
  term_in_lang_id                 INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_property_terms_property_id ON /*_*/wb_property_terms (property_id);
CREATE INDEX /*i*/wb_property_terms_term_in_lang_id ON /*_*/wb_property_terms (term_in_lang_id);

CREATE TABLE IF NOT EXISTS /*_*/wb_term_in_lang (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  term_type                       INT unsigned       NOT NULL,
  text_in_lang_id                 INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_term_in_lang_type ON /*_*/wb_term_in_lang (term_type);
CREATE INDEX /*i*/wb_term_in_lang_text_in_lang_id ON /*_*/wb_term_in_lang (text_in_lang_id);

CREATE TABLE IF NOT EXISTS /*_*/wb_text_in_lang (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  text_language                   VARCHAR(10)        NOT NULL,
  term_text_id                    INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_text_in_lang_language ON /*_*/wb_text_in_lang (text_language);
CREATE INDEX /*i*/wb_text_in_lang_text_id ON /*_*/wb_text_in_lang (term_text_id);

CREATE TABLE IF NOT EXISTS /*_*/wb_term_text (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  text                            VARCHAR(255)       NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_term_text ON /*_*/wb_term_text (text);

CREATE TABLE IF NOT EXISTS /*_*/wb_term_type (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  type_name                       VARCHAR(45)        NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_term_type_name ON /*_*/wb_term_type (type_name);