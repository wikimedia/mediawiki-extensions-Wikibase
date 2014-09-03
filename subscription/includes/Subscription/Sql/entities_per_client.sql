CREATE TABLE IF NOT EXISTS /*_*/wb_entities_per_client (
  epc_row_id        BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  epc_entity_type   VARBINARY(32)  NOT NULL, -- the type of the entity being used
  epc_entity_id     VARBINARY(255) NOT NULL, -- the ID of the entity being used
  epc_site_id       VARBINARY(48)  NOT NULL  -- the ID of the client site using the entity
) /*$wgDBTableOptions*/;

-- record each entity/client pair only once
CREATE UNIQUE INDEX /*i*/epc_entity_id ON /*_*/wb_entities_per_client ( epc_entity_id, epc_site_id );

-- look up usage by site (filter by type)
CREATE INDEX /*i*/epc_site_id ON /*_*/wb_entities_per_client ( epc_site_id, epc_entity_type );
