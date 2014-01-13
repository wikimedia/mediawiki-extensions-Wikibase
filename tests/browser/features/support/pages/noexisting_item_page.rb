# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for a non existing item

class NonExistingItemPage
  include PageObject
  page_url WIKIDATA_REPO_URL + ITEM_NAMESPACE + ITEM_ID_PREFIX + "xy"

  span(:firstHeading, :xpath => "//h1[@id='firstHeading']/span")
  link(:specialLogLink, :css => "div#mw-content-text > div > p > span > a:nth-child(1)")
  link(:specialCreateNewItemLink, :css => "div#mw-content-text > div > p > a:nth-child(2)")
end
