-- General info and links:
-- -----------------------
--
-- These tables represent a normalized form of the wb_terms table.
--
-- The wb_terms table history leading to this point can be all followed starting
-- from this epic https://phabricator.wikimedia.org/T208425
--
-- The current solution is part of that epic. work on it can be followed starting
-- from this ticket https://phabricator.wikimedia.org/T219175
--
-- This design is normalized for the sake of reducing duplication and redundancy
-- that accounted for ~70% of the disk space occupied by wb_terms table as of
-- March 2019. It requires few joins for queris, and impact on performance
-- will be addressed as the need arise.
--
-- Note on splitting Items and Properties:
-- ---------------------------------------
-- wb_item_terms an wb_property_terms are two tables with similar structure.
-- original draft had them combined in one table, but further discussion lead
-- to the decision of splitting them for the following reasons:
-- - items are lot more and will grow faster than properties, which means
--   when querying only for properties we need not to look at a gigantic
--   index that includes items as well, but rather a very small one.
-- - conceptually, items and properties are two different entity types, and
--   since different entity types might have different constraints and maybe
--   even structure, splitting them into corrisponding tables saves us from
--   ending up with a wide polymorphic table again (lesson learned from
--   wb_terms table itself)
--
-- Note on Entity IDs:
-- ------------------
-- Since wikidata entity ids are fixed and known to always be Q (like in Q123),
--  entity ids are stored as integers after dropping that prefix, in both
-- wb_item_terms and wb_property_terms tables.
-- e.g. entity id Q123 has integer id of 123.
--
-- when a new entity type gets introdced into the instance then one can either:
-- - store it in wb_item_terms/wb_property_items if it has the same prefix (Q), or
-- - create a separate table similar to wb_item_terms in structure for those
--   entities if the prefix is different.


-- stores a record per term per item per language. this table is expected to be the longest one
-- in this group of tables.
--
-- term text, type and language are normalized further through wb_term_in_lang
-- table.
CREATE TABLE IF NOT EXISTS /*_*/wbt_item_terms (
  wbit_id                                BIGINT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbit_item_id                           INT    unsigned       NOT NULL,
  wbit_term_in_lang_id                   INT    unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wbt_item_terms_item_id ON /*_*/wbt_item_terms (wbit_item_id);
CREATE UNIQUE INDEX /*i*/wbt_item_terms_term_in_lang_id_item_id ON /*_*/wbt_item_terms (wbit_term_in_lang_id, wbit_item_id);

-- stores a record per term per property per language.
--
-- term text, type and language are normalized further through wb_term_in_lang
-- table.
CREATE TABLE IF NOT EXISTS /*_*/wbt_property_terms (
  wbpt_id                                INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbpt_property_id                       INT unsigned       NOT NULL,
  wbpt_term_in_lang_id                   INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wbt_property_terms_property_id ON /*_*/wbt_property_terms (wbpt_property_id);
CREATE UNIQUE INDEX /*i*/wbt_property_terms_term_in_lang_id_property_id ON /*_*/wbt_property_terms (wbpt_term_in_lang_id, wbpt_property_id);

-- stores a record per term per text per language.
--
-- term text and language are normalized further through wb_text_in_lang table.
CREATE TABLE IF NOT EXISTS /*_*/wbt_term_in_lang (
  wbtl_id                          INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbtl_type_id                     INT unsigned       NOT NULL,
  wbtl_text_in_lang_id             INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wbt_term_in_lang_type_id_text_in ON /*_*/wbt_term_in_lang (wbtl_type_id);
CREATE UNIQUE INDEX /*i*/wbt_term_in_lang_text_in_lang_id_lang_id ON /*_*/wbt_term_in_lang (wbtl_text_in_lang_id, wbtl_type_id);

-- stores a record per term text per language.
--
-- text is normalized through wb_term_text table.
CREATE TABLE IF NOT EXISTS /*_*/wbt_text_in_lang (
  wbxl_id                         INT unsigned       NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbxl_language                   VARBINARY(10)      NOT NULL,
  wbxl_text_id                    INT unsigned       NOT NULL
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/wbt_text_in_lang_language ON /*_*/wbt_text_in_lang (wbxl_language);
CREATE UNIQUE INDEX /*i*/wbt_text_in_lang_text_id_text_id ON /*_*/wbt_text_in_lang (wbxl_text_id, wbxl_language);

-- stores a record per text value that are used in different terms
-- in different languages.
CREATE TABLE IF NOT EXISTS /*_*/wbt_text (
  wbx_id                             INT unsigned         NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wbx_text                           VARBINARY(255)       NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wbt_text_text ON /*_*/wbt_text (wbx_text);

-- normalized term type names
CREATE TABLE IF NOT EXISTS /*_*/wbt_type (
  wby_id                                INT unsigned         NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wby_name                              VARBINARY(45)        NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wbt_type_name ON /*_*/wbt_type (wby_name);
