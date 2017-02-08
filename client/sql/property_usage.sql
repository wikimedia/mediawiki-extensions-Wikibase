CREATE TABLE IF NOT EXISTS /*_*/wbc_property_usage (
  pu_entity_id               VARBINARY(255) NOT NULL, -- the ID of the entity being used
  pu_property_id             VARBINARY(255) NOT NULL,  -- the property ID used from the entity.
  pu_page_id                 INT NOT NULL,            -- the ID of the page that uses the entities.
  pu_user_specified_value    Boolean,                 -- true if property value specified in lua module
  pu_property_exists         Boolean,                 -- true if entity table has value for property          

 ) /*$wgDBTableOptions*/;