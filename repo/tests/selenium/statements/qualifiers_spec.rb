# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for qualifiers UI

require 'spec_helper'

num_items = 3
num_props_cm = 2
num_props_item = 1

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

cm_statement_value = "Vespa_crabro_head_01.jpg"
cm_qualifier_value = "Blason_CH_Canton_Valais_3D.svg"
cm_qualifier_value_changed = "BlueFeather.jpg"

describe "Check qualifiers UI" do
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
    properties_item.each do |property|
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

  context "Check qualifiers UI" do
    it "should check qualifiers buttons behavior" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement?.should be_true
        page.editFirstStatement
        page.editFirstStatement?.should be_false
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.qualifiersContainer?.should be_true
        page.addQualifier?.should be_true
        page.addQualifier
        page.addQualifier?.should be_false
        page.addQualifierDisabled?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.entitySelectorInput?.should be_true
        page.entitySelectorInput.should == ""
        page.removeQualifierLine1?.should be_true
        page.removeQualifierLine1
        page.removeQualifierLine1?.should be_false
        page.entitySelectorInput?.should be_false
        page.addQualifier?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true

        page.addQualifier
        page.addQualifier?.should be_false
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.entitySelectorInput?.should be_true
        page.entitySelectorInput.should == ""
        page.entitySelectorInput = generate_random_string(10)
        ajax_wait
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.qualifierValueInput1?.should be_false
        page.addQualifier?.should be_false
        page.removeQualifierLine1?.should be_true

        page.entitySelectorInput_element.clear
        page.entitySelectorInput = properties_cm[1]["label"]
        ajax_wait
        page.wait_for_qualifier_value_box
        page.qualifierValueInput1?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.addQualifier?.should be_false
        page.removeQualifierLine1?.should be_true

        page.removeQualifierLine1
        page.removeQualifierLine1?.should be_false
        page.entitySelectorInput?.should be_false
        page.qualifierValueInput1?.should be_false
        page.addQualifier?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true

        page.addQualifier
        page.addQualifier?.should be_false
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.entitySelectorInput?.should be_true
        page.entitySelectorInput.should == ""
        page.entitySelectorInput = properties_cm[1]["label"]
        ajax_wait
        page.wait_for_qualifier_value_box
        page.qualifierValueInput1?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.addQualifier?.should be_false
        page.removeQualifierLine1?.should be_true
        page.qualifierValueInput1 = cm_qualifier_value
        ajax_wait
        page.qualifierValueInput1?.should be_true
        page.saveStatement?.should be_true
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.addQualifier?.should be_true
        page.removeQualifierLine1?.should be_true
        page.addQualifier
        page.addQualifier?.should be_false
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.entitySelectorInput?.should be_true
        page.entitySelectorInput.should == properties_cm[1]["label"]
        page.qualifierValueInput1?.should be_true
        page.qualifierValueInput1.should == cm_qualifier_value
        page.entitySelectorInput2?.should be_true
        page.entitySelectorInput2.should == ""
        page.qualifierValueInput2?.should be_false
        page.removeQualifierLine1?.should be_true
        page.removeQualifierLine2?.should be_true
        page.removeQualifierLine2
        page.addQualifier?.should be_true
        page.entitySelectorInput?.should be_true
        page.entitySelectorInput.should == properties_cm[1]["label"]
        page.entitySelectorInput2?.should be_false
        page.qualifierValueInput1?.should be_true
        page.qualifierValueInput1.should == cm_qualifier_value
        page.saveStatement?.should be_true
        page.cancelStatement?.should be_true

        page.entitySelectorInput = generate_random_string(10)
        page.addQualifier?.should be_false
        page.removeQualifierLine1?.should be_true
        page.entitySelectorInput?.should be_true
        page.qualifierValueInput1?.should be_false
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true

        page.cancelStatement
        page.addQualifier?.should be_false
        page.editFirstStatement?.should be_true
      end
    end

    it "should check adding one qualifier" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement?.should be_true
        page.editFirstStatement
        page.qualifiersContainer?.should be_true
        page.addQualifier?.should be_true
        page.addQualifier
        page.entitySelectorInput?.should be_true
        page.entitySelectorInput.should == ""
        page.entitySelectorInput = properties_item[0]["label"]
        ajax_wait
        page.wait_for_qualifier_value_box
        page.qualifierValueInput1?.should be_true
        page.qualifierValueInput1.should == ""
        page.qualifierValueInput1 = items[1]["label"]
        ajax_wait
        page.saveStatement?.should be_true
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
        page.qualifierProperty1?.should be_true
        page.qualifierProperty1.should == properties_item[0]["label"]
        page.qualifierValue1?.should be_true
        page.qualifierValue1.should == items[1]["label"]
        page.qualifierValueLink1?.should be_true
        page.qualifierValueLink1
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == items[1]["label"]
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.qualifierPropertyLink1?.should be_true
        page.qualifierPropertyLink1
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == properties_item[0]["label"]
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.qualifierValueLink1?.should be_true
        page.qualifierValueLink1
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == items[1]["label"]
      end
    end

    it "should check editing a qualifier" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement?.should be_true
        page.editFirstStatement
        page.qualifiersContainer?.should be_true
        page.addQualifier?.should be_true
        page.removeQualifierLine1?.should be_true
        page.qualifierValueInput1?.should be_true
        page.qualifierValueInput1.should == items[1]["label"]
        page.qualifierProperty1?.should be_true
        page.qualifierProperty1.should == properties_item[0]["label"]

        page.qualifierValueInput1 = items[2]["label"]
        ajax_wait
        page.saveStatement?.should be_true
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
        page.qualifierProperty1?.should be_true
        page.qualifierProperty1.should == properties_item[0]["label"]
        page.qualifierValue1?.should be_true
        page.qualifierValue1.should == items[2]["label"]
        page.qualifierValueLink1?.should be_true
        page.qualifierValueLink1
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == items[2]["label"]
      end
    end

    it "should check editing multiline qualifiers" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement?.should be_true
        page.editFirstStatement
        page.qualifiersContainer?.should be_true
        page.removeQualifierLine1?.should be_true
        page.qualifierValueInput1?.should be_true
        page.qualifierValueInput1.should == items[2]["label"]
        page.qualifierProperty1?.should be_true
        page.qualifierProperty1.should == properties_item[0]["label"]
        page.addQualifier?.should be_true
        page.addQualifier

        page.entitySelectorInput?.should be_true
        page.entitySelectorInput.should == ""
        page.entitySelectorInput = properties_cm[1]["label"]
        ajax_wait
        page.wait_for_qualifier_value_box
        page.qualifierValueInput2?.should be_true
        page.qualifierValueInput2.should == ""
        page.qualifierValueInput2 = cm_qualifier_value
        ajax_wait
        page.saveStatement?.should be_true
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished

        page.qualifierProperty1?.should be_true
        page.qualifierProperty1.should == properties_item[0]["label"]
        page.qualifierPropertyLink1?.should be_true
        page.qualifierValue1?.should be_true
        page.qualifierValue1.should == items[2]["label"]
        page.qualifierValueLink1?.should be_true
        page.qualifierProperty2?.should be_true
        page.qualifierProperty2.should == properties_cm[1]["label"]
        page.qualifierPropertyLink2?.should be_true
        page.qualifierValue2?.should be_true
        page.qualifierValue2.should == cm_qualifier_value
        page.qualifierValueLink2?.should be_true
        @browser.refresh
        page.wait_for_entity_to_load
        page.qualifierProperty1?.should be_true
        page.qualifierProperty1.should == properties_item[0]["label"]
        page.qualifierPropertyLink1?.should be_true
        page.qualifierValue1?.should be_true
        page.qualifierValue1.should == items[2]["label"]
        page.qualifierValueLink1?.should be_true
        page.qualifierProperty2?.should be_true
        page.qualifierProperty2.should == properties_cm[1]["label"]
        page.qualifierPropertyLink2?.should be_true
        page.qualifierValue2?.should be_true
        page.qualifierValue2.should == cm_qualifier_value
        page.qualifierValueLink2?.should be_true

        page.editFirstStatement?.should be_true
        page.editFirstStatement
        page.removeQualifierLine1?.should be_true
        page.removeQualifierLine2?.should be_true
        page.qualifierValueInput1 = items[1]["label"]
        ajax_wait
        page.saveStatement?.should be_true
        page.qualifierValueInput2 = cm_qualifier_value_changed
        ajax_wait
        page.saveStatement?.should be_true
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished

        page.qualifierProperty1?.should be_true
        page.qualifierProperty1.should == properties_item[0]["label"]
        page.qualifierPropertyLink1?.should be_true
        page.qualifierValue1?.should be_true
        page.qualifierValue1.should == items[1]["label"]
        page.qualifierValueLink1?.should be_true
        page.qualifierProperty2?.should be_true
        page.qualifierProperty2.should == properties_cm[1]["label"]
        page.qualifierPropertyLink2?.should be_true
        page.qualifierValue2?.should be_true
        page.qualifierValue2.should == cm_qualifier_value_changed
        page.qualifierValueLink2?.should be_true
        @browser.refresh
        page.wait_for_entity_to_load
        page.qualifierProperty1?.should be_true
        page.qualifierProperty1.should == properties_item[0]["label"]
        page.qualifierPropertyLink1?.should be_true
        page.qualifierValue1?.should be_true
        page.qualifierValue1.should == items[1]["label"]
        page.qualifierValueLink1?.should be_true
        page.qualifierProperty2?.should be_true
        page.qualifierProperty2.should == properties_cm[1]["label"]
        page.qualifierPropertyLink2?.should be_true
        page.qualifierValue2?.should be_true
        page.qualifierValue2.should == cm_qualifier_value_changed
        page.qualifierValueLink2?.should be_true
      end
    end

    it "should check removing multiple qualifiers" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.removeQualifierLine1?.should be_true
        page.removeQualifierLine2?.should be_true
        page.removeQualifierLine2
        page.removeQualifierLine1?.should be_true
        page.removeQualifierLine2?.should be_false
        page.removeQualifierLine1
        page.removeQualifierLine1?.should be_false
        page.removeQualifierLine2?.should be_false
        page.saveStatement?.should be_true
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished

        page.qualifierProperty1?.should be_false
        page.qualifierProperty1?.should be_false
        page.qualifierValue1?.should be_false
        page.qualifierValue2?.should be_false
        @browser.refresh
        page.wait_for_entity_to_load
        page.qualifierProperty1?.should be_false
        page.qualifierProperty1?.should be_false
        page.qualifierValue1?.should be_false
        page.qualifierValue2?.should be_false
      end
    end
  end

  after :all do
    # tear down
  end
end
