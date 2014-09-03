CREATE TABLE IF NOT EXISTS /*_*/wbc_entity_usage (
  eu_entity_type    VARBINARY(32) NOT NULL, -- the type of the entity being used
  eu_entity_id      VARBINARY(255) NOT NULL, -- the ID of the entity being used
  eu_aspect         ENUM ( "sitelinks", "label", or "all" ) NOT NULL,           -- the aspect of the entity.
  eu_page_id        INT NOT NULL
) /*$wgDBTableOptions*/;

-- record one usage per page per aspect of an entity
CREATE PRIMARY KEY ( eu_entity_id, eu_aspect, eu_page_id ) ON /*_*/wbc_entityusage;

-- look up (and especially, delete) usage entris by page id
CREATE INDEX /*i*/eu_page_id ( eu_page_id, eu_entity_id ) ON /*_*/wbc_entityusage;

-- look up usage by entity type (filter by namespace)
CREATE INDEX /*i*/eu_entity_type ( eu_entity_type ) ON /*_*/wbc_entityusage;
