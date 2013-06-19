# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for statements

require 'spec_helper'

num_items = 1
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

statement_value = generate_random_string(10) + '.jpg'
statement_value_changed = generate_random_string(10) + '.jpg'

describe "Check statements UI" do
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
  end

  context "Check statements UI" do
    it "should check statement buttons behavior" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.addStatement?.should be_true
        page.addStatement
        page.addStatement?.should be_false
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.entitySelectorInput?.should be_true
        page.statementValueInput?.should be_false
        page.cancelStatement
        page.addStatement?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_false
        page.entitySelectorInput?.should be_false
        page.statementValueInput?.should be_false
      end
    end
    it "should check snaktype selector behavior", :exclude_firefox => true do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = properties_cm[0]["label"]
        ajax_wait
        page.wait_for_property_value_box
        page.snaktypeSelectorIcon?.should be_true
        page.snaktypeSelectorIcon_element.click
        page.statementValueInput?.should be_true
        page.snaktypeSelectorValue?.should be_true
        page.snaktypeSelectorSomevalue?.should be_true
        page.snaktypeSelectorNovalue?.should be_true
        page.snaktypeSelectorSomevalue
        page.statementValueInput?.should be_false
        page.snaktypeSelectorIcon_element.click
        page.statementValueInput?.should be_false
        page.snaktypeSelectorNovalue
        page.statementValueInput?.should be_false
        page.snaktypeSelectorIcon_element.click
        page.statementValueInput?.should be_false
        page.snaktypeSelectorValue
        page.statementValueInput?.should be_true
        page.cancelStatement
        page.snaktypeSelectorIcon?.should be_false
      end
    end
    it "should check entity suggester behavior" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = properties_cm[0]["label"][0..8]
        ajax_wait
        page.wait_for_entity_selector_list
        page.statementValueInput?.should be_false
        page.saveStatement?.should be_false
        page.firstEntitySelectorLink?.should be_true
        page.firstEntitySelectorLabel?.should be_true
        page.firstEntitySelectorDescription?.should be_true
        page.firstEntitySelectorLabel.should == properties_cm[0]["label"]
        page.firstEntitySelectorDescription.should == properties_cm[0]["description"]
        page.firstEntitySelectorLink
        ajax_wait
        page.wait_for_property_value_box
        page.statementValueInput?.should be_true
        page.entitySelectorInput_element.clear
        page.entitySelectorInput = " " # element.clear seems to not trigger the correct events
        page.entitySelectorInput_element.clear
        page.statementValueInput?.should be_false
        page.entitySelectorInput = properties_cm[0]["label"]
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInput?.should be_true
        page.saveStatement?.should be_false
        page.statementValueInput = statement_value
        page.saveStatement?.should be_true
        page.cancelStatement
        page.addStatement?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_false
        page.entitySelectorInput?.should be_false
        page.statementValueInput?.should be_false
      end
    end
    it "should check error handling" do
      on_page(ItemPage) do |page|
        js_snippet = "wikibase.AbstractedRepoApi.prototype.setClaim ="+
        "function(claim,baseRevId){var d = new $.Deferred();return d.reject('some_error_code',{error:{info:'some info'}});}"
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        @browser.execute_script(js_snippet);
        page.addStatement
        page.entitySelectorInput = properties_cm[0]["label"]
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInput = statement_value
        page.saveStatement
        ajax_wait
        page.wbErrorDiv?.should be_true
        page.cancelStatement
      end
    end
    it "should check adding a statement" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.add_statement(properties_cm[0]["label"], statement_value)
        page.statement1Name?.should be_true
        page.statement1ClaimValue1?.should be_true
        page.addStatement?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_false
        page.editFirstStatement?.should be_true
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == statement_value
        page.statement1Link
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == properties_cm[0]["label"]
        @browser.back
        @browser.refresh
        page.wait_for_entity_to_load
        page.addStatement?.should be_true
        page.editFirstStatement?.should be_true
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == statement_value
        page.statement1Link
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == properties_cm[0]["label"]
      end
    end
    it "should check removing of claim/statement" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.removeClaimButton?.should be_true
        page.removeClaimButton
        ajax_wait
        page.wait_for_statement_request_finished
        page.addStatement?.should be_true
        page.editFirstStatement?.should be_false
        page.statement1Name?.should be_false
        page.statement1ClaimValue1?.should be_false
        @browser.refresh
        page.addStatement?.should be_true
        page.editFirstStatement?.should be_false
        page.statement1Name?.should be_false
        page.statement1ClaimValue1?.should be_false
      end
    end
    it "should check ESC/RETURN button behavior" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        # ESCAPE key
        page.addStatement?.should be_true
        page.addStatement
        page.addStatement?.should be_false
        page.entitySelectorInput = properties_cm[0]["label"]
        page.entitySelectorInput_element.send_keys :escape
        page.addStatement?.should be_true
        page.entitySelectorInput?.should be_false
        page.addStatement
        page.entitySelectorInput = properties_cm[0]["label"]
        ajax_wait
        page.wait_for_property_value_box
        page.statementValueInput?.should be_true
        page.statementValueInput = statement_value
        page.statementValueInput_element.send_keys :escape
        page.addStatement?.should be_true
        page.entitySelectorInput?.should be_false
        page.statementValueInput?.should be_false
        # RETURN key
        page.addStatement
        page.entitySelectorInput = properties_cm[0]["label"]
        ajax_wait
        page.wait_for_property_value_box
        page.entitySelectorInput_element.send_keys :return
        ajax_wait
        page.statementValueInput?.should be_true
        page.statementValueInput = statement_value
        page.statementValueInput_element.send_keys :return
        ajax_wait
        page.wait_for_statement_request_finished
        page.addStatement?.should be_true
        page.entitySelectorInput?.should be_false
        page.statementValueInput?.should be_false
        page.remove_all_claims
      end
    end
    it "should check adding multiple claims to same statement" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        values = [generate_random_string(10) + '.jpg', generate_random_string(10) + '.jpg', generate_random_string(10) + '.jpg']
        page.add_statement(properties_cm[0]["label"], values[0])
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == values[0]
        page.statement1ClaimValue2?.should be_false
        page.statement1ClaimValue3?.should be_false
        page.add_statement(properties_cm[0]["label"], values[1])
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == values[0]
        page.statement1ClaimValue2.should == values[1]
        page.statement1ClaimValue3?.should be_false
        page.add_statement(properties_cm[0]["label"], values[2])
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == values[0]
        page.statement1ClaimValue2.should == values[1]
        page.statement1ClaimValue3.should == values[2]
        @browser.refresh
        page.statement1ClaimValue1.should == values[0]
        page.statement1ClaimValue2.should == values[1]
        page.statement1ClaimValue3.should == values[2]
        page.remove_all_claims
        page.statement1ClaimValue1?.should be_false
        page.statement1Name?.should be_false
        @browser.refresh
        page.statement1ClaimValue1?.should be_false
        page.statement1Name?.should be_false
        page.add_statement(properties_cm[0]["label"], values[0])
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == values[0]
        page.statement1ClaimValue2?.should be_false
        page.statement1ClaimValue3?.should be_false
        page.add_claim_to_first_statement(values[1])
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == values[0]
        page.statement1ClaimValue2.should == values[1]
        page.statement1ClaimValue3?.should be_false
        page.add_claim_to_first_statement(values[2])
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == values[0]
        page.statement1ClaimValue2.should == values[1]
        page.statement1ClaimValue3.should == values[2]
        @browser.refresh
        page.statement1ClaimValue1.should == values[0]
        page.statement1ClaimValue2.should == values[1]
        page.statement1ClaimValue3.should == values[2]
        page.remove_all_claims
      end
    end
    it "should check adding multiple statements & claims" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        statement1_values = [generate_random_string(10) + '.jpg', generate_random_string(10) + '.jpg']
        statement2_values = [generate_random_string(10) + '.jpg', generate_random_string(10) + '.jpg']
        page.add_statement(properties_cm[0]["label"], statement1_values[0])
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == statement1_values[0]
        page.add_statement(properties_cm[1]["label"], statement2_values[0])
        page.statement2Name.should == properties_cm[1]["label"]
        page.statement2ClaimValue1.should == statement2_values[0]
        page.add_statement(properties_cm[0]["label"], statement1_values[1])
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue2.should == statement1_values[1]
        page.add_statement(properties_cm[1]["label"], statement2_values[1])
        page.statement2Name.should == properties_cm[1]["label"]
        page.statement2ClaimValue2.should == statement2_values[1]
        page.remove_all_claims
        page.statement1Name?.should be_false
      end
    end
    it "should check button behavior when editing a statement" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        # make sure no claims displayed on page
        page.remove_all_claims
        page.add_statement(properties_cm[0]["label"], statement_value)
        page.editFirstStatement
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.removeClaimButton?.should be_true
        page.entitySelectorInput?.should be_false
        page.statementValueInput?.should be_true
        page.statementValueInput.should == statement_value
        page.statement1Name.should == properties_cm[0]["label"]
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.statementValueInput_element.clear
        page.saveStatement?.should be_false
        page.statementValueInput = statement_value_changed
        page.saveStatement?.should be_true
        page.cancelStatement
        page.statement1Name?.should be_true
        page.statement1ClaimValue1?.should be_true
        page.addStatement?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_false
        page.editFirstStatement?.should be_true
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == statement_value
      end
    end
    it "should check editing a statement" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.statementValueInput_element.clear
        page.statementValueInput = statement_value_changed
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
        page.addReferenceToFirstClaim?.should be_true
        page.statement1Name?.should be_true
        page.statement1ClaimValue1?.should be_true
        page.addStatement?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_false
        page.editFirstStatement?.should be_true
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == statement_value_changed
        @browser.refresh
        page.wait_for_entity_to_load
        page.addStatement?.should be_true
        page.editFirstStatement?.should be_true
        page.statement1Name.should == properties_cm[0]["label"]
        page.statement1ClaimValue1.should == statement_value_changed
      end
    end
  end

  after :all do
    # tear down
  end
end
