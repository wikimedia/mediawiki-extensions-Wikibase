-- Licence: GNU GPL v2+

-- Links id+type to page ids.
CREATE TABLE IF NOT EXISTS /*_*/wb_entity_per_page (
  epp_entity_id                  INT unsigned        NOT NULL, -- Id of the entity
  epp_entity_type                VARBINARY(32)       NOT NULL, -- Type of the entity
  epp_page_id                    INT unsigned        NOT NULL, -- Id of the page that stores the entity
  epp_redirect_target            VARBINARY(255)      DEFAULT NULL -- Target entity, in case the row represents a redirect
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_epp_entity ON /*_*/wb_entity_per_page (epp_entity_id, epp_entity_type);
CREATE UNIQUE INDEX /*i*/wb_epp_page ON /*_*/wb_entity_per_page (epp_page_id);
CREATE INDEX /*i*/epp_redirect_target ON /*_*/wb_entity_per_page (epp_redirect_target);
