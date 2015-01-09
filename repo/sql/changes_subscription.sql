CREATE TABLE IF NOT EXISTS /*_*/wb_changes_subscription (
  cs_row_id         BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  cs_entity_id      VARBINARY(255) NOT NULL, -- the ID of the entity subscribed to
  cs_subscriber_id  VARBINARY(255) NOT NULL  -- the ID of the subscriber (e.g. a domain name or database name)
) /*$wgDBTableOptions*/;

-- look up a subscription, or all subscribers of an entity
CREATE UNIQUE INDEX /*i*/cs_entity_id ON /*_*/wb_changes_subscription ( cs_entity_id, cs_subscriber_id );

-- look up all subscriptions of a subscriber
CREATE INDEX /*i*/cs_subscriber_id ON /*_*/wb_changes_subscription ( cs_subscriber_id ) ;
