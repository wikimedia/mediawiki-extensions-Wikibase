# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for a non existing item

class NonExistingItemPage
  include PageObject

  env = MediawikiSelenium::Environment.load_default
  page_url '<%= env.lookup(:mediawiki_url) %><%= env.lookup(:item_namespace, default: "") %><%= env.lookup(:item_id_prefix) %>xy'

  h1(:first_heading, class: 'firstHeading')
  link(:special_log_link, css: '.noarticletext a:nth-child(1)')
end
