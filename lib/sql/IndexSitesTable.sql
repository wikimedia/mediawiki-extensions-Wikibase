ALTER TABLE /*_*/sites CHANGE site_link_equivalents site_link_navigation bool NOT NULL;

CREATE UNIQUE INDEX /*i*/sites_global_key ON /*_*/sites (site_global_key);
CREATE INDEX /*i*/sites_type ON /*_*/sites (site_type);
CREATE INDEX /*i*/sites_group ON /*_*/sites (site_group);
CREATE UNIQUE INDEX /*i*/sites_local_key ON /*_*/sites (site_local_key);
CREATE INDEX /*i*/sites_link_inline ON /*_*/sites (site_link_inline);
CREATE INDEX /*i*/sites_link_navigation ON /*_*/sites (site_link_navigation);
CREATE INDEX /*i*/sites_forward ON /*_*/sites (site_forward);
CREATE INDEX /*i*/sites_allow_transclusion ON /*_*/sites (site_allow_transclusion);