CREATE TABLE IF NOT EXISTS /*_*/wb_item_terms (
  wbit_id                                INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbit_item_id                           INT unsigned       NOT NULL,
  wbit_term_in_lang_id                   INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_item_terms_item_id ON /*_*/wb_item_terms (wbit_item_id);
CREATE INDEX /*i*/wb_item_terms_term_in_lang_id ON /*_*/wb_item_terms (wbit_term_in_lang_id);

CREATE TABLE IF NOT EXISTS /*_*/wb_property_terms (
  wbpt_id                                INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbpt_property_id                       INT unsigned       NOT NULL,
  wbpt_term_in_lang_id                   INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_property_terms_property_id ON /*_*/wb_property_terms (wbpt_property_id);
CREATE INDEX /*i*/wb_property_terms_term_in_lang_id ON /*_*/wb_property_terms (wbpt_term_in_lang_id);

CREATE TABLE IF NOT EXISTS /*_*/wb_term_in_lang (
  wbtil_id                               INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbtil_term_type                        INT unsigned       NOT NULL,
  wbtil_text_in_lang_id                  INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_term_in_lang_type ON /*_*/wb_term_in_lang (wbtil_term_type);
CREATE INDEX /*i*/wb_term_in_lang_text_in_lang_id ON /*_*/wb_term_in_lang (wbtil_text_in_lang_id);

CREATE TABLE IF NOT EXISTS /*_*/wb_text_in_lang (
  wbtxtl_id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbtxtl_text_language                   VARCHAR(10)        NOT NULL,
  wbtxtl_term_text_id                    INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wb_text_in_lang_language ON /*_*/wb_text_in_lang (wbtxtl_text_language);
CREATE INDEX /*i*/wb_text_in_lang_text_id ON /*_*/wb_text_in_lang (wbtxtl_term_text_id);

CREATE TABLE IF NOT EXISTS /*_*/wb_term_text (
  wbttext_id                             INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbttext_text                           VARCHAR(255)       NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_term_text ON /*_*/wb_term_text (wbttext_text);

CREATE TABLE IF NOT EXISTS /*_*/wb_term_type (
  wbtt_id                                INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbtt_type_name                         VARCHAR(45)        NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_term_type_name ON /*_*/wb_term_type (wbtt_type_name);