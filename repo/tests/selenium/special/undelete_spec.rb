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
sitelinks = [["en", "Edinburgh"], ["hu", "Edinburgh"]]

describe "Check undelete" do

  context "undelete test setup" do
    it "should create item, enter label, description, aliases & sitelinks" do
      visit_page(LoginPage) do |page|
        page.logout_user
      end
      visit_page(ItemPage) do |page|
        page.create_new_item(label_a, description_a)
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.add_aliases([alias_a])
        page.add_sitelinks(sitelinks)
      end
    end
  end

  context "delete the item" do
    it "should login as admin and delete the item" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(DeleteItemPage) do |page|
        page.delete_item
      end
    end
  end

  context "undelete the item" do
    it "should login as admin and undelete the item" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(UndeleteItemPage) do |page|
        page.undelete_item
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.itemLabelSpan.should == label_a
        page.itemDescriptionSpan.should == description_a
        page.get_number_of_sitelinks_from_counter.should == 2
        page.get_nth_alias(1).text.should == alias_a
      end
    end
  end

  context "undelete test teardown" do
    it "should remove all sitelinks" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_sitelinks_to_load
        page.remove_all_sitelinks
      end
      visit_page(LoginPage) do |page|
        page.logout_user
      end
    end
  end
end
