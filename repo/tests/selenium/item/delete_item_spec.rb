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
  before :all do
    # set up
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
      page.wait_for_entity_to_load
      page.add_aliases([alias_a, alias_b, alias_c])
    end
  end
  context "create item, add some stuff, delete the item" do
    it "should login with admin and delete item, then check if it got removed properly" do
      visit_page(SearchPage) do |page|
        page.searchText= label
        page.searchSubmit
        page.count_search_results.should == 1
        page.firstResultLabelSpan_element.text.should == label
        page.searchText= alias_b
        page.searchSubmit
        page.count_search_results.should == 1
        page.firstResultLabelSpan_element.text.should == label
      end
      visit_page(RepoLoginPage) do |page|
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
    end
  end
  after :all do
    # tear down: logout
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end
end
