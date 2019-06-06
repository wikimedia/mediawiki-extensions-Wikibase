# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for a non existing item

class NonExistingItemPage
  include PageObject
  page_url ENV['WIKIDATA_REPO_URL'] + ENV['ITEM_NAMESPACE'] + ENV['ITEM_ID_PREFIX'] + 'xy'

  h1(:first_heading, class: 'firstHeading')
  link(:special_log_link, css: '.noarticletext a:nth-child(1)')
end
