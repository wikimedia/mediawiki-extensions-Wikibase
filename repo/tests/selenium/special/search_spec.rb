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

  context "Search test setup" do
    it "should create item, enter label, description and aliases" do
      visit_page(ItemPage) do |page|
        page.create_new_item(label, description)
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases
        page.aliasesInputEmpty= alias_a
        page.aliasesInputEmpty= alias_b
        page.aliasesInputEmpty= alias_c
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
      end
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
        page.countSearchResults.should == 1
        page.firstResultLabelSpan_element.text.should == label
        page.firstResultSearchMatch_element.text.should == label
      end
    end
  end

  context "Check for search results when searching by aliases" do
    it "should check for correct results by aliases" do
      visit_page(SearchPage) do |page|
        page.searchText= alias_a
        page.searchSubmit
        page.countSearchResults.should == 1
        page.firstResultLabelSpan_element.text.should == label
        page.firstResultSearchMatch_element.text.should == alias_a
        page.searchText= alias_b
        page.searchSubmit
        page.countSearchResults.should == 1
        page.firstResultLabelSpan_element.text.should == label
        page.firstResultSearchMatch_element.text.should == alias_b
        page.searchText= alias_c
        page.searchSubmit
        page.countSearchResults.should == 1
        page.firstResultLabelSpan_element.text.should == label
        page.firstResultSearchMatch_element.text.should == alias_c
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
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label
      end
    end
  end
end
