# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for search page

class SearchPage < ItemPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:Search"
  text_field(:searchText, :id => "searchText")
  button(:searchSubmit, :text => "Search")
  div(:searchResultDiv, :class => "searchresults")
  unordered_list(:searchResults, :class => "mw-search-results")
  paragraph(:noResults, :class => "mw-search-nonefound")
  span(:firstResultLabelSpan, :class => "wb-itemlink-label")
  span(:firstResultIdSpan, :class => "wb-itemlink-id")
  link(:firstResultLink, :xpath => "//div[@class='mw-search-result-heading']/a")

  def count_search_results
    count = 0
    searchResults_element.each do |resultElem|
      count = count+1
    end
    return count
  end
end
