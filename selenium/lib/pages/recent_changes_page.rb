# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# base page object for recent changes special page

class RecentChangesPage < ItemPage
  include PageObject
  unordered_list(:recentChanges, :class => "special")
  span(:firstResultLabelSpan, :class => "wb-itemlink-label")
  span(:firstResultIdSpan, :class => "wb-itemlink-id")
  link(:firstResultLink, :xpath => "//ul[@class='special']/li/span/a")

  def count_search_results
    count = 0
    searchResults_element.each do |resultElem|
      count = count+1
    end
    return count
  end
end
