-- Licence: GNU GPL v2+

-- Badges to sitelinks
CREATE TABLE IF NOT EXISTS /*_*/wb_badges_per_sitelink (
  bps_badge_id                   VARBINARY(32)       NOT NULL, -- Id of badge entity
  bps_site_page                  VARCHAR(255)        NOT NULL, -- Name of the page that has the badge
  bps_site_id                    VARBINARY(32)       NOT NULL  -- Site of the page
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_bps_badges ON /*_*/wb_badges_per_sitelink (bps_badge_id, bps_site_page, bps_site_id);
CREATE INDEX /*i*/wb_bps_pages_per_site ON /*_*/wb_badges_per_sitelink (bps_site_page, bps_site_id);
