# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for a non existing item

class NonExistingItemPage
  include PageObject
  page_url ENV["WIKIDATA_REPO_URL"] + ENV["ITEM_NAMESPACE"] + ENV["ITEM_ID_PREFIX"] + "xy"

  span(:first_heading, :xpath => "//h1[@id='firstHeading']/span")
  link(:special_log_link, :css => "div#mw-content-text > div > p > span > a:nth-child(1)")
  link(:special_create_new_item_link, :css => "div#mw-content-text > div > p > a:nth-child(2)")
end
