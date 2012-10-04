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
  link(:wlArticleLink1, :xpath => "//ul[contains(@class, 'special')]/li/span[contains(@class, 'mw-title')]/a")
  span(:wlArticleComment1, :xpath => "//ul[contains(@class, 'special')]/li/span[contains(@class, 'comment')]")

end
