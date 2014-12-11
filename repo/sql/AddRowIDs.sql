-- Create unique row IDs, so we can efficiently update large tables later.

ALTER TABLE /*_*/wb_items_per_site
ADD ips_row_id
BIGINT PRIMARY KEY auto_increment FIRST;

ALTER TABLE /*_*/wb_terms
ADD COLUMN term_row_id
BIGINT PRIMARY KEY auto_increment FIRST;
