-- Licence: GNU GPL v2+

-- Badges to site links
CREATE TABLE IF NOT EXISTS /*_*/wb_badges_per_sitelink (
  bps_row_id                     BIGINT unsigned     NOT NULL PRIMARY KEY AUTO_INCREMENT, -- row ID
  bps_badge_id                   VARBINARY(32)       NOT NULL, -- Id of badge item
  bps_site_id                    VARBINARY(32)       NOT NULL, -- Site of the page that has the badge
  bps_site_page                  VARCHAR(310)        NOT NULL -- Name of the page
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/wb_bps_badges ON /*_*/wb_badges_per_sitelink (bps_badge_id, bps_site_id, bps_site_page);
CREATE INDEX /*i*/wb_bps_badges_per_site ON /*_*/wb_badges_per_sitelink (bps_badge_id, bps_site_id);
