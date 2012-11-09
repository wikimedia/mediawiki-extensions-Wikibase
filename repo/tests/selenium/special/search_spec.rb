# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for search

require 'spec_helper'

nonexisting_label = generate_random_string(10)
label = generate_random_string(10)
description = generate_random_string(20)
alias_a = generate_random_string(5)
alias_b = generate_random_string(5)
alias_c = generate_random_string(5)

describe "Check functionality search" do
  before :all do
    # set up: create item and add aliases
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
      page.wait_for_entity_to_load
      page.add_aliases([alias_a, alias_b, alias_c])
    end
  end

  context "Check for search results when searching for something nonexistant" do
    it "should check for no results" do
      visit_page(SearchPage) do |page|
        page.searchText?.should be_true
        page.searchSubmit?.should be_true
        page.searchText= nonexisting_label
        page.searchSubmit
        page.searchResultDiv?.should be_true
        page.searchResults?.should be_false
        page.noResults?.should be_true
      end
    end
  end

  context "Check for search results when searching by label" do
    it "should check for correct results by label" do
      visit_page(SearchPage) do |page|
        page.searchText= label
        page.searchSubmit
        page.searchResultDiv?.should be_true
        page.searchResults?.should be_true
        page.noResults?.should be_false
        page.count_search_results.should == 1
        page.firstResultLabelSpan_element.text.should == label
      end
    end
  end

  context "Check for search results when searching by aliases" do
    it "should check for correct results by aliases" do
      visit_page(SearchPage) do |page|
        page.searchText= alias_a
        page.searchSubmit
        page.count_search_results.should == 1
        page.firstResultLabelSpan_element.text.should == label
        page.searchText= alias_b
        page.searchSubmit
        page.count_search_results.should == 1
        page.firstResultLabelSpan_element.text.should == label
        page.searchText= alias_c
        page.searchSubmit
        page.count_search_results.should == 1
        page.firstResultLabelSpan_element.text.should == label
      end
    end
  end

  context "Check for working link in search result" do
    it "should check for correct results by label" do
      visit_page(SearchPage) do |page|
        page.searchText= label
        page.searchSubmit
        page.firstResultLink?.should be_true
        page.firstResultLink
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
      end
    end
  end

  after :all do
    # tear down
  end
end
