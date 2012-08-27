# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item deletion

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
alias_a = generate_random_string(5)
alias_b = generate_random_string(5)
alias_c = generate_random_string(5)

describe "Check functionality of item deletion" do
  context "create item, add some stuff, delete the item" do
    it "should create an item, login with admin and delete it, then check if removed properly" do
      visit_page(ItemPage) do |page|
        page.create_new_item(label, description)
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.add_aliases([alias_a, alias_b, alias_c])
      end
      visit_page(SearchPage) do |page|
        page.searchText= label
        page.searchSubmit
        page.count_search_results.should == 1
        page.firstResultLabelSpan_element.text.should == label
        page.firstResultSearchMatch_element.text.should == label
        page.searchText= alias_b
        page.searchSubmit
        page.count_search_results.should == 1
        page.firstResultLabelSpan_element.text.should == label
        page.firstResultSearchMatch_element.text.should == alias_b
      end
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      visit_page(DeleteItemPage) do |page|
        page.delete_item
      end
      visit_page(SearchPage) do |page|
        page.searchText= label
        page.searchSubmit
        page.searchResultDiv?.should be_true
        page.searchResults?.should be_false
        page.noResults?.should be_true
        page.searchText= alias_c
        page.searchSubmit
        page.searchResultDiv?.should be_true
        page.searchResults?.should be_false
        page.noResults?.should be_true
      end
      visit_page(LoginPage) do |page|
        page.logout_user
      end
    end
  end
end
