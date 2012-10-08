# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for undelete

require 'spec_helper'

label_a = generate_random_string(10)
description_a = generate_random_string(20)
label_b = generate_random_string(10)
description_b = generate_random_string(20)
alias_a = generate_random_string(5)
alias_b = generate_random_string(5)
sitelinks = [["en", "Edinburgh"], ["hu", "Edinburgh"]]
item_id_a = 0
item_id_b = 0

describe "Check undelete" do
  before :all do
    # set up: create item + aliases & sitelinks, login as admin and delete item
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
    visit_page(CreateItemPage) do |page|
      item_id_a = page.create_new_item(label_a, description_a)
      page.wait_for_entity_to_load
      page.add_aliases([alias_a])
      page.add_sitelinks(sitelinks)
    end
    visit_page(RepoLoginPage) do |page|
      page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
    end
    on_page(DeleteItemPage) do |page|
      page.delete_item
    end
  end

  context "undelete the item" do
    it "should login as admin and undelete the item" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(UndeleteItemPage) do |page|
        page.undelete_item(item_id_a)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_a
        page.entityDescriptionSpan.should == description_a
        page.get_number_of_sitelinks_from_counter.should == 2
        page.get_nth_alias(1).text.should == alias_a
      end
    end
  end

  context "delete the item again" do
    it "should login as admin and delete the item" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(DeleteItemPage) do |page|
        page.delete_item
      end
    end
  end

  context "create a second item with same sitelinks as our first item" do
    it "should create item and add sitelinks" do
      visit_page(RepoLoginPage) do |page|
        page.logout_user
      end
      visit_page(CreateItemPage) do |page|
        item_id_b = page.create_new_item(label_b, description_b)
        page.wait_for_entity_to_load
        page.add_aliases([alias_b])
        page.add_sitelinks(sitelinks)
      end
    end
  end

  context "trying to undelete first item" do
    it "should login as admin and try to undelete the first item" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(UndeleteItemPage) do |page|
        page.undelete_item(item_id_a)
        page.undeleteErrorDiv?.should be_true
        page.conflictingItemLink
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_b
        page.entityDescriptionSpan.should == description_b
        page.get_number_of_sitelinks_from_counter.should == 2
        page.get_nth_alias(1).text.should == alias_b
      end
    end
  end

  after :all do
    # tear down: remove all sitelinks, logout
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.remove_all_sitelinks
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end
end
