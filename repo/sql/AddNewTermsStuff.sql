CREATE TABLE IF NOT EXISTS /*_*/wb_item_terms (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  item_id                         INT unsigned       NOT NULL,
  term_in_lang_id                 INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE TABLE IF NOT EXISTS /*_*/wb_property_terms (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  property_id                     INT unsigned       NOT NULL,
  term_in_lang_id                 INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE TABLE IF NOT EXISTS /*_*/wb_term_in_lang (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  term_type                       INT unsigned       NOT NULL,
  text_in_lang_id                 INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE TABLE IF NOT EXISTS /*_*/wb_text_in_lang (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  text_language                   VARCHAR(10)        NOT NULL,
  term_text_id                    INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE TABLE IF NOT EXISTS /*_*/wb_term_text (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  text                            VARCHAR(255)       NOT NULL
) /*$wgDBTableOptions*/;

CREATE TABLE IF NOT EXISTS /*_*/wb_term_type (
  id                              INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  type_name                       VARCHAR(45)        NOT NULL
) /*$wgDBTableOptions*/;