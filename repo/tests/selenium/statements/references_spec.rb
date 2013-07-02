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
cm_reference_value2 = "Nyan.jpg"
cm_reference_value_changed = "Denkmal.png"
cm_reference_value_changed2 = "Dynamite-5.svg"

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
    it "should check references buttons behavior" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.referenceContainer?.should be_true
        page.referenceHeading?.should be_true
        page.referenceEditHeading?.should be_false
        page.addReferenceToFirstClaim?.should be_true

        page.addReferenceToFirstClaim
        page.referenceEditHeading?.should be_true
        page.addReferenceToFirstClaim?.should be_false
        page.addReferenceToFirstClaimDisabled?.should be_true
        page.saveReference?.should be_false
        page.saveReferenceDisabled?.should be_true
        page.removeReference?.should be_false
        page.cancelReference?.should be_true
        page.addReferenceLine?.should be_false
        page.addReferenceLineDisabled?.should be_true
        page.entitySelectorInput?.should be_true
        page.removeReferenceLine1?.should be_false
        page.removeReferenceLine1Disabled?.should be_true
        page.cancelReference

        page.addReferenceToFirstClaim?.should be_true
        page.referenceEditHeading?.should be_false
        page.saveReference?.should be_false
        page.removeReference?.should be_false
        page.cancelReference?.should be_false
        page.addReferenceLine?.should be_false
        page.removeReferenceLine1?.should be_false
        page.entitySelectorInput?.should be_false
        page.addReferenceToFirstClaim
        page.saveReference?.should be_false
        page.entitySelectorInput = generate_random_string(10)
        page.saveReference?.should be_false
        page.referenceValueInput?.should be_false
        page.addReferenceLine?.should be_false
        page.removeReferenceLine1?.should be_false

        page.entitySelectorInput_element.clear
        page.entitySelectorInput = properties_cm[1]["label"]
        ajax_wait
        page.wait_for_reference_value_box
        page.referenceValueInput?.should be_true
        page.saveReference?.should be_false
        page.cancelReference?.should be_true
        page.removeReference?.should be_false
        page.addReferenceLine?.should be_false
        page.removeReferenceLine1?.should be_false
        random_ref_value = generate_random_string(10)
        page.referenceValueInput = random_ref_value
        page.saveReference?.should be_true
        page.cancelReference?.should be_true
        page.addReferenceLine?.should be_true
        page.removeReferenceLine1?.should be_false
        page.addReferenceLine
        page.removeReferenceLine1?.should be_true
        page.removeReferenceLine2?.should be_true
        page.addReferenceLine?.should be_false
        page.saveReference?.should be_false
        page.removeReferenceLine2
        page.removeReferenceLine1?.should be_false
        page.removeReferenceLine2?.should be_false
        page.addReferenceLine?.should be_true
        page.saveReference?.should be_true
        page.entitySelectorInput?.should be_true
        page.referenceValueInput?.should be_true
        page.entitySelectorInput.should == properties_cm[1]["label"]
        page.referenceValueInput.should == random_ref_value

        page.entitySelectorInput_element.clear
        page.entitySelectorInput = " "
        page.saveReference?.should be_false
        page.addReferenceLine?.should be_false
        page.removeReferenceLine1?.should be_false
        page.referenceValueInput?.should be_false
        page.entitySelectorInput = properties_cm[1]["label"]
        ajax_wait
        page.wait_for_reference_value_box
        page.referenceValueInput_element.clear
        page.referenceValueInput = generate_random_string(10)
        page.saveReference?.should be_true
        page.addReferenceLine?.should be_true
        page.cancelReference?.should be_true
        page.removeReferenceLine1?.should be_false

        page.addReferenceLine
        page.addReferenceLine?.should be_false
        page.removeReferenceLine1?.should be_true
        page.removeReferenceLine2?.should be_true
        page.removeReferenceLine1
        page.removeReferenceLine1?.should be_false
        page.removeReferenceLine2?.should be_false
        page.entitySelectorInput.should == ''
        page.referenceValueInput?.should be_false
        page.addReferenceLine?.should be_false
        page.saveReference?.should be_false
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
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1Property.should == properties_cm[1]["label"]
        page.reference1Value.should == cm_reference_value
        page.reference1PropertyLink
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == properties_cm[1]["label"]
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1ValueLink
        page.articleTitle.include?("File:" + cm_reference_value).should be_true
      end
    end

    it "should check editing a reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
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
        page.referenceValueInput_element.send_keys :return
        ajax_wait
        page.wait_for_statement_request_finished
        page.reference1Property.should == properties_cm[1]["label"]
        page.reference1Value.should == cm_reference_value_changed

        # try to edit the reference twice in a row
        page.editReference1
        page.referenceValueInput_element.clear
        page.referenceValueInput = cm_reference_value
        page.saveReference
        ajax_wait
        page.wait_for_statement_request_finished
        page.reference1Property.should == properties_cm[1]["label"]
        page.reference1Value.should == cm_reference_value
        page.editReference1
        page.referenceValueInput_element.clear
        page.referenceValueInput = cm_reference_value_changed
        page.saveReference
        ajax_wait
        page.wait_for_statement_request_finished
        page.reference1Property.should == properties_cm[1]["label"]
        page.reference1Value.should == cm_reference_value_changed

        page.reference1ValueLink
        page.articleTitle.include?("File:" + cm_reference_value_changed).should be_true
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1Property.should == properties_cm[1]["label"]
        page.reference1Value.should == cm_reference_value_changed
      end
    end

    it "should check removing a reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
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

    it "should check adding multiline reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.referenceContainer?.should be_true
        page.referenceHeading?.should be_true
        page.addReferenceToFirstClaim?.should be_true
        page.addReferenceToFirstClaim
        page.entitySelectorInput = properties_cm[0]["label"]
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_reference_value_box
        page.referenceValueInput = cm_reference_value
        page.saveReference?.should be_true
        page.addReferenceLine?.should be_true
        page.addReferenceLine
        page.saveReference?.should be_false
        page.entitySelectorInput?.should be_true
        page.entitySelectorInput2?.should be_true
        page.entitySelectorInput2 = properties_cm[1]["label"]
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_reference_value_box
        page.referenceValueInput?.should be_true
        page.referenceValueInput2?.should be_true
        page.referenceValueInput2 = cm_reference_value2
        page.saveReference?.should be_true
        page.saveReference
        ajax_wait
        page.wait_for_statement_request_finished
        page.reference1Property.should == properties_cm[0]["label"]
        page.reference1Value.should == cm_reference_value
        page.reference1Property2.should == properties_cm[1]["label"]
        page.reference1Value2.should == cm_reference_value2

        page.reference1ValueLink
        page.articleTitle.include?("File:" + cm_reference_value).should be_true
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1ValueLink2
        page.articleTitle.include?("File:" + cm_reference_value2).should be_true
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1PropertyLink
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == properties_cm[0]["label"]
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1PropertyLink2
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == properties_cm[1]["label"]

        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1Property.should == properties_cm[0]["label"]
        page.reference1Value.should == cm_reference_value
        page.reference1Property2.should == properties_cm[1]["label"]
        page.reference1Value2.should == cm_reference_value2
      end
    end

    it "should check editing multiline reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.editReference1?.should be_true
        page.editReference1
        page.editReference1?.should be_false
        page.addReferenceToFirstClaim?.should be_false
        page.saveReference?.should be_false
        page.removeReference?.should be_true
        page.cancelReference?.should be_true
        page.addReferenceLine?.should be_true
        page.removeReferenceLine1?.should be_true
        page.removeReferenceLine2?.should be_true
        page.entitySelectorInput?.should be_false
        page.entitySelectorInput2?.should be_false
        page.referenceValueInput?.should be_true
        page.referenceValueInput2?.should be_true
        page.referenceValueInput.should == cm_reference_value
        page.referenceValueInput2.should == cm_reference_value2
        page.reference1Property.should == properties_cm[0]["label"]
        page.reference1Property2.should == properties_cm[1]["label"]
        page.removeReferenceLine1
        page.saveReference?.should be_true
        page.addReferenceLine
        page.saveReference?.should be_false
        page.cancelReference
        page.editReference1
        page.referenceValueInput_element.clear
        page.referenceValueInput2_element.clear
        page.referenceValueInput = cm_reference_value_changed
        page.referenceValueInput2 = cm_reference_value_changed2
        page.saveReference?.should be_true
        page.saveReference
        ajax_wait
        page.wait_for_statement_request_finished

        page.reference1ValueLink
        page.articleTitle.include?("File:" + cm_reference_value_changed).should be_true
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1ValueLink2
        page.articleTitle.include?("File:" + cm_reference_value_changed2).should be_true

        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1Property.should == properties_cm[0]["label"]
        page.reference1Value.should == cm_reference_value_changed
        page.reference1Property2.should == properties_cm[1]["label"]
        page.reference1Value2.should == cm_reference_value_changed2
      end
    end

    it "should check removing of multiline reference" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.editReference1?.should be_true
        page.editReference1
        page.removeReferenceLine1?.should be_true
        page.removeReferenceLine2?.should be_true
        page.saveReference?.should be_false
        page.removeReferenceLine2
        page.saveReference?.should be_true
        page.saveReference
        ajax_wait
        page.wait_for_statement_request_finished
        page.reference1Property.should == properties_cm[0]["label"]
        page.reference1Value?.should be_true
        page.reference1Property2?.should be_false
        page.reference1Value2?.should be_false
        @browser.refresh
        page.wait_for_entity_to_load
        page.toggle_reference_section
        page.reference1Property.should == properties_cm[0]["label"]
        page.reference1Value?.should be_true
        page.reference1Property2?.should be_false
        page.reference1Value2?.should be_false

        page.editReference1?.should be_true
        page.editReference1
        page.removeReferenceLine1?.should be_false
        page.removeReferenceLine2?.should be_false
        page.saveReference?.should be_false
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
