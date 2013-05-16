# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for statements with deleted items

require 'spec_helper'

num_items = 2
num_props_item = 1

# items
count = 0
items = Array.new
while count < num_items do
  items.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20)})
  count = count + 1
end

# item properties
count = 0
properties_item = Array.new
while count < num_props_item do
  properties_item.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20), "datatype"=>"Item"})
  count = count + 1
end

describe "Check deleted item in statements UI" do
  before :all do
    # set up: create items & properties, add statements, delete item
    items.each do |item|
      visit_page(CreateItemPage) do |page|
        item['id'] = page.create_new_item(item['label'], item['description'])
        item['url'] = page.current_url
      end
    end
    properties_item.each do |property|
      visit_page(NewPropertyPage) do |page|
        property['id'] = page.create_new_property(property['label'], property['description'], property['datatype'])
        property['url'] = page.current_url
      end
    end
    on_page(ItemPage) do |page|
      page.navigate_to items[0]["url"]
      page.wait_for_entity_to_load
      page.add_statement(properties_item[0]["label"], items[1]["label"])
      page.add_reference_to_first_claim(properties_item[0]["label"], items[1]["label"])
    end
    visit_page(RepoLoginPage) do |page|
      page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
    end
    visit_page(DeleteEntityPage) do |page|
      page.delete_entity(items[1]["url"])
    end
  end

  context "Check statements UI with deleted item" do
    it "should check correct UI behavior on deleted item" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.statement1ClaimValue1Nolink.should_not == items[1]["label"]
        page.statement1ClaimValue1Nolink.include?(items[1]["id"]).should be_true
        page.statement1ClaimValue1Nolink.include?("Deleted item").should be_true

        page.toggle_reference_section
        page.reference1Value.should_not == items[1]["label"]
        page.reference1Value.include?(items[1]["id"]).should be_true
        page.reference1Value.include?("Deleted item").should be_true
        page.reference1ValueLink?.should be_false
        page.editReference1
        page.referenceValueInput.should == ""
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
