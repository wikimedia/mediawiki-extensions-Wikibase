# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for references

require 'spec_helper'

num_items = 1
num_props_cm = 2
num_props_item = 0

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

cm_statement_value = "Louisiana 462.svg"
cm_reference_value = "Lousiana Red Kammgarn.jpg"
cm_reference_value_changed = "Denkmal.png"

describe "Check references UI" do
  before :all do
    # set up: create items & properties & add statement
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
    on_page(ItemPage) do |page|
      page.navigate_to items[0]["url"]
      page.wait_for_entity_to_load
      page.add_statement(properties_cm[0]["label"], cm_statement_value)
    end
  end

  context "Check references UI" do
    it "should check references buttons behaviour" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.referenceContainer?.should be_true
        page.referenceHeading?.should be_true
        page.addReferenceToFirstClaim?.should be_true
        page.addReferenceToFirstClaim
        page.addReferenceToFirstClaim?.should be_false
        page.saveReference?.should be_false
        page.removeReference?.should be_false
        page.cancelReference?.should be_true
        page.cancelReference
        page.addReferenceToFirstClaim?.should be_true
        page.saveReference?.should be_false
        page.removeReference?.should be_false
        page.cancelReference?.should be_false
        page.addReferenceToFirstClaim
        page.saveReference?.should be_false
        page.entitySelectorInput = generate_random_string(10)
        page.saveReference?.should be_false
        page.entitySelectorInput_element.clear
        page.entitySelectorInput = properties_cm[1]["label"]
        ajax_wait
        page.wait_for_reference_value_box
        page.referenceValueInput.should be_true
        page.saveReference?.should be_false
        page.cancelReference?.should be_true
        page.removeReference?.should be_false
        page.referenceValueInput = generate_random_string(10)
        page.saveReference?.should be_true
        page.cancelReference?.should be_true
        page.entitySelectorInput_element.clear
        page.entitySelectorInput = " "
        # TODO: this will fail because of bug 44543
        # page.saveReference?.should be_false
        page.referenceValueInput?.should be_false
        page.entitySelectorInput = properties_cm[1]["label"]
        ajax_wait
        page.wait_for_reference_value_box
        page.referenceValueInput = generate_random_string(10)
        page.saveReference?.should be_true
        page.cancelReference?.should be_true
        page.cancelReference
      end
    end

    it "should check adding one reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.referenceContainer?.should be_true
        page.referenceHeading?.should be_true
        page.addReferenceToFirstClaim?.should be_true
        page.addReferenceToFirstClaim
        page.entitySelectorInput = properties_cm[1]["label"]
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_reference_value_box
        page.referenceValueInput = cm_reference_value
        page.saveReference?.should be_true
        page.saveReference
        ajax_wait
        page.wait_for_statement_request_finished
        page.reference1Property.should == properties_cm[1]["label"]
        page.reference1Value.should == cm_reference_value
        page.reference1ValueLink
        page.articleTitle.include?("File:" + cm_reference_value).should be_true
        @browser.back
        @browser.refresh
        page.wait_for_entity_to_load
        page.reference1Property.should == properties_cm[1]["label"]
        page.reference1Value.should == cm_reference_value
        page.reference1PropertyLink
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == properties_cm[1]["label"]
        @browser.back
        page.wait_for_entity_to_load
        page.reference1ValueLink
        page.articleTitle.include?("File:" + cm_reference_value).should be_true
      end
    end

    it "should check editing a reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editReference1?.should be_true
        page.editReference1
        page.editReference1?.should be_false
        page.addReferenceToFirstClaim?.should be_false
        page.saveReference?.should be_false
        page.removeReference?.should be_true
        page.cancelReference?.should be_true
        page.referenceValueInput.should == cm_reference_value
        page.referenceValueInput_element.clear
        page.referenceValueInput = cm_reference_value_changed
        page.saveReference?.should be_true
        page.removeReference?.should be_true
        page.cancelReference?.should be_true
        page.editReference1?.should be_false
        page.addReferenceToFirstClaim?.should be_false
        page.saveReference
        ajax_wait
        page.wait_for_statement_request_finished
        page.reference1Property.should == properties_cm[1]["label"]
        page.reference1Value.should == cm_reference_value_changed
        page.reference1ValueLink
        page.articleTitle.include?("File:" + cm_reference_value_changed).should be_true
        @browser.back
        @browser.refresh
        page.wait_for_entity_to_load
        page.reference1Property.should == properties_cm[1]["label"]
        page.reference1Value.should == cm_reference_value_changed
      end
    end

    it "should check removing a reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editReference1?.should be_true
        page.editReference1
        page.removeReference?.should be_true
        page.removeReference
        ajax_wait
        page.wait_for_statement_request_finished
        page.reference1Property?.should be_false
        page.reference1Value?.should be_false
        page.editReference1?.should be_false
        page.addReferenceToFirstClaim?.should be_true
        page.saveReference?.should be_false
        page.removeReference?.should be_false
        page.cancelReference?.should be_false
      end
    end

  end

  after :all do
    # tear down
  end
end
