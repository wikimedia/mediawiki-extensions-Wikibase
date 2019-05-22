-- normalized term type names
CREATE TABLE IF NOT EXISTS /*_*/wbt_type (
  wby_id                                INT unsigned         NOT NULL PRIMARY KEY AUTO_INCREMENT,
  wby_name                              VARBINARY(45)        NOT NULL
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wbt_type_name ON /*_*/wbt_type (wby_name);
