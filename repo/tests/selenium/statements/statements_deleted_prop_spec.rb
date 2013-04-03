# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for statements with deleted properties

require 'spec_helper'

num_items = 2#3
num_props_cm = 1#2
num_props_item = 1#2

cm_string = "Abc.jpg"

# TODO: this whole entitycreation stuff should be refactored out from here
# items
count = 0
items = Array.new
while count < num_items do
  items.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20)})
  count = count + 1
end

# commons media properties
count = 0
properties_cm = Array.new
while count < num_props_cm do
  properties_cm.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20), "datatype"=>"Commons media file"})
  count = count + 1
end

# item properties
count = 0
properties_item = Array.new
while count < num_props_item do
  properties_item.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20), "datatype"=>"Item"})
  count = count + 1
end

statement_value = generate_random_string(10)
statement_value_changed = generate_random_string(10)

describe "Check deleted properties in statements UI" do
  before :all do
    # set up: create items & properties
    items.each do |item|
      visit_page(CreateItemPage) do |page|
        item['id'] = page.create_new_item(item['label'], item['description'])
        item['url'] = page.current_url
      end
    end
    properties_cm.each do |property|
      visit_page(NewPropertyPage) do |page|
        property['id'] = page.create_new_property(property['label'], property['description'], property['datatype'])
        property['url'] = page.current_url
      end
    end
    properties_item.each do |property|
      visit_page(NewPropertyPage) do |page|
        property['id'] = page.create_new_property(property['label'], property['description'], property['datatype'])
        property['url'] = page.current_url
      end
    end
  end

  context "Check statements UI with deleted CM type property" do
    it "should create statements & add reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.add_statement(properties_cm[0]["label"], cm_string)
        page.add_reference_to_first_claim(properties_cm[0]["label"], cm_string)
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == cm_string
        page.reference1Property.should == properties_cm[0]["label"]
        page.reference1Value.should == cm_string
      end
    end
    it "should delete related property" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      visit_page(DeleteEntityPage) do |page|
        page.delete_entity(properties_cm[0]["url"])
      end
    end
    it "should check correct UI behavior on deleted property" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.statement1Name.should_not == properties_cm[0]["label"]
        page.statement1Name.include?(properties_cm[0]["id"]).should be_true
        page.statement1Name.include?("Deleted property").should be_true
        # TODO: In contrast to the setclaimvalue API module, the setclaim API module does not cause
        # an error when editing a statement that features a deleted property. Re-evaluate the
        # following test procedure as soon as this contradiction is solved.
        #page.editFirstStatement
        #page.statementValueInput.should == cm_string
        #page.statementValueInput_element.clear
        #page.statementValueInput = "changed"
        #page.saveStatement
        #ajax_wait
        #page.wbErrorDiv?.should be_true
        #page.cancelStatement
        page.toggle_reference_section
        page.reference1Property.should_not == properties_cm[0]["label"]
        page.reference1Property.include?(properties_cm[0]["id"]).should be_true
        page.reference1Property.include?("Deleted property").should be_true
        page.reference1ValueLink?.should be_false
        page.editReference1
        page.referenceValueInput.should == cm_string
        # TODO: should it be allowed to edit a reference whose property was deleted?
        #page.referenceValueInput_element.clear
        #page.referenceValueInput = "changed"
        #page.saveReference
        #ajax_wait
        #page.wbErrorDiv?.should be_true
        #page.cancelReference
      end
    end
  end

  context "Check statements UI with deleted ITEM type property" do
    it "should create statements & add reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[1]["url"]
        page.wait_for_entity_to_load
        page.add_statement(properties_item[0]["label"], items[0]["label"])
        page.add_reference_to_first_claim(properties_item[0]["label"], items[0]["label"])
        page.statement1Name.should == properties_item[0]["label"]
        page.statement1ClaimValue1.should == items[0]["label"]
        page.reference1Property.should == properties_item[0]["label"]
        page.reference1Value.should == items[0]["label"]
      end
    end
    it "should delete related property" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      visit_page(DeleteEntityPage) do |page|
        page.delete_entity(properties_item[0]["url"])
      end
    end
    it "should check correct UI behavior on deleted property" do
      on_page(ItemPage) do |page|
        page.navigate_to items[1]["url"]
        page.wait_for_entity_to_load
        page.statement1Name.should_not == properties_item[0]["label"]
        page.statement1Name.include?(properties_item[0]["id"]).should be_true
        page.statement1Name.include?("Deleted property").should be_true

        page.toggle_reference_section
        page.reference1Property.should_not == properties_item[0]["label"]
        page.reference1Property.include?(properties_item[0]["id"]).should be_true
        page.reference1Property.include?("Deleted property").should be_true

        # TODO: should it be allowed to edit a reference whose property was deleted?
        #page.referenceValueInput_element.clear
        #page.referenceValueInput = "changed"
        #page.saveReference
        #ajax_wait
        #page.wbErrorDiv?.should be_true
        #page.cancelReference
      end
    end
  end

  after :all do
    # tear down
  end
end
