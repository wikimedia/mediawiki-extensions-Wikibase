-- property info table --

CREATE TABLE IF NOT EXISTS /*_*/wb_property_info (
  pi_property_id    INT unsigned        NOT NULL,
  pi_type           VARBINARY(32)       NOT NULL,
  pi_info           BLOB                NOT NULL,
  PRIMARY KEY ( pi_property_id ),
  INDEX pi_type ( pi_type )
) /*$wgDBTableOptions*/;

