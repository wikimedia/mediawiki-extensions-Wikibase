# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for watchlist

class WatchlistPage
  include PageObject

  page_url WIKI_CLIENT_URL + 'Special:Watchlist'

  list_item(:wlFirstResult, :xpath => "//ul[@class='special']/li")
  link(:wlFirstResultDiffLink, :xpath => "//ul[@class='special']/li/a[1]")
  link(:wlFirstResultHistoryLink, :xpath => "//ul[@class='special']/li/a[2]")
  link(:wlFirstResultLabelLink, :xpath => "//ul[@class='special']/li/a[3]")
  link(:wlFirstResultIDLink, :xpath => "//ul[@class='special']/li/a[4]")
  link(:wlFirstResultUserLink, :xpath => "//ul[@class='special']/li/a[5]")
  link(:wlShowWikidataToggle, :id => "wb-toggle-link")
  span(:clientFirstResultComment, :xpath => "//ul[@class='special']/li/span[@class='comment']")

end
