-- Update row IDs to BIGINT, so we don't run out of bits.

ALTER TABLE /*_*/wb_terms
MODIFY term_row_id BIGINT unsigned NOT NULL auto_increment;

ALTER TABLE /*_*/wb_items_per_site
MODIFY ips_row_id BIGINT unsigned NOT NULL auto_increment;
