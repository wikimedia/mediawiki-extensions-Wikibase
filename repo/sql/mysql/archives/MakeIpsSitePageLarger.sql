-- Update ips_site_page to VARCHAR(310) per T99459

ALTER TABLE /*_*/wb_items_per_site
MODIFY ips_site_page VARCHAR(310) NOT NULL;
