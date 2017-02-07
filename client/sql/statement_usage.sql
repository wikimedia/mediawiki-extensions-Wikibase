CREATE TABLE IF NOT EXISTS /*_*/wbc_statement_usage (
  su_entity_id               VARBINARY(255) NOT NULL, -- the ID of the entity being used
  su_property_id             VARBINARY(255) NOT NULL, -- the property ID of the main snak associated with the statement used from the entity
  su_page_id                 INT UNSIGNED NOT NULL,   -- the ID of the page that uses the entities
  su_statement_exists        Boolean,                 -- true if entity table has value for property

 ) /*$wgDBTableOptions*/;

-- record one usage per page per property associated with a statement of an entity
CREATE UNIQUE INDEX /*i*/su_entity_id ON /*_*/wbc_statement_usage PRIMARY KEY ( su_entity_id, su_property_id, su_page_id );