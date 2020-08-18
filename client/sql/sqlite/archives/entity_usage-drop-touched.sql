-- get rid of the eu_touched column

-- Workaround for sqlite - T147300

CREATE TABLE IF NOT EXISTS /*_*/wbc_entity_usage_tmp (
  eu_row_id         BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  eu_entity_id      VARBINARY(255) NOT NULL, -- the ID of the entity being used
  eu_aspect         VARBINARY(37) NOT NULL,  -- the aspect of the entity. See EntityUsage::XXX_USAGE for possible values.
  eu_page_id        INT NOT NULL             -- the ID of the page that uses the entities.
) /*$wgDBTableOptions*/;

INSERT INTO /*_*/wbc_entity_usage_tmp
  SELECT eu_row_id, eu_entity_id, eu_aspect, eu_page_id
  FROM /*_*/wbc_entity_usage;

DROP TABLE /*_*/wbc_entity_usage;

ALTER TABLE /*_*/wbc_entity_usage_tmp RENAME TO /*_*/wbc_entity_usage;

-- record one usage per page per aspect of an entity
CREATE UNIQUE INDEX /*i*/eu_entity_id ON /*_*/wbc_entity_usage ( eu_entity_id, eu_aspect, eu_page_id );

-- look up (and especially, delete) usage entries by page id
CREATE INDEX /*i*/eu_page_id ON /*_*/wbc_entity_usage ( eu_page_id, eu_entity_id ) ;
