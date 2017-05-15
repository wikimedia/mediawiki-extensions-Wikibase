-- Patch to add the epp_redirect_target to wb_entity_per_page.
-- Licence: GNU GPL v2+

ALTER TABLE /*_*/wb_entity_per_page
ADD COLUMN epp_redirect_target
VARBINARY(255) DEFAULT NULL;

CREATE INDEX /*i*/epp_redirect_target ON /*_*/wb_entity_per_page (epp_redirect_target);
