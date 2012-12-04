# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for statements

require 'spec_helper'

item_label = generate_random_string(10)
item_description = generate_random_string(20)
prop_a_label = generate_random_string(10)
prop_a_description = generate_random_string(20)
prop_a_datatype = "Commons media file"
statement_value = generate_random_string(10)
statement_value_changed = generate_random_string(10)

describe "Check statements UI" do
  before :all do
    # set up: create item & property
    visit_page(CreateItemPage) do |page|
      page.create_new_item(item_label, item_description)
    end
    visit_page(NewPropertyPage) do |page|
      page.create_new_property(prop_a_label, prop_a_description, prop_a_datatype)
    end
  end

  context "Check statements UI" do
    it "should check statement buttons behaviour" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement?.should be_true
        page.addStatement
        page.addStatement?.should be_false
        # TODO: still broken in UI - save-button shoud be disabled - bug caught in statements_bugs_spec
        #page.saveStatement?.should be_false
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
    it "should check entity suggestor behaviour" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_a_label[0..8]
        ajax_wait
        page.wait_for_entity_selector_list
        page.statementValueInput?.should be_false
        # TODO: still broken in UI - save-button shoud be disabled - bug caught in statements_bugs_spec
        #page.saveStatement?.should be_false
        page.firstEntitySelectorLink?.should be_true
        page.firstEntitySelectorLabel.should == prop_a_label
        page.firstEntitySelectorDescription.should == prop_a_description
        page.firstEntitySelectorLink
        ajax_wait
      # TODO: still broken in UI - property-value input-box should be shown when selecting an entity by click - bug caught in statements_bugs_spec
        #page.wait_for_property_value_box
        #page.statementValueInput?.should be_true
        page.entitySelectorInput_element.clear
        # TODO: still broken in UI - property-value input-box should be removed when property field is empty - bug caught in statements_bugs_spec
        #page.statementValueInput?.should be_false
        page.entitySelectorInput = prop_a_label
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInput?.should be_true
        # TODO: still broken in UI - save-button shoud be disabled - bug caught in statements_bugs_spec
        #page.saveStatement?.should be_false
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
    it "should check adding a statement" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_statement(prop_a_label, statement_value)
        page.firstClaimName?.should be_true
        page.firstClaimValue?.should be_true
        page.addStatement?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_false
        page.editFirstStatement?.should be_true
        page.firstClaimName.should == prop_a_label
        page.firstClaimValue.should == statement_value
        @browser.refresh
        page.wait_for_entity_to_load
        page.addStatement?.should be_true
        page.editFirstStatement?.should be_true
        page.firstClaimName.should == prop_a_label
        page.firstClaimValue.should == statement_value
      end
    end
    it "should check button behaviour when editing a statement" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.entitySelectorInput?.should be_false
        page.statementValueInput?.should be_true
        page.statementValueInput.should == statement_value
        page.firstClaimName.should == prop_a_label
        # TODO: still broken in UI - save-button shoud be disabled - bug caught in statements_bugs_spec
        #page.saveStatement?.should be_false
        page.cancelStatement?.should be_true
        page.statementValueInput_element.clear
        # TODO: still broken in UI - save-button shoud be disabled - bug caught in statements_bugs_spec
        #page.saveStatement?.should be_false
        page.statementValueInput = statement_value_changed
        page.saveStatement?.should be_true
        page.cancelStatement
        page.firstClaimName?.should be_true
        page.firstClaimValue?.should be_true
        page.addStatement?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_false
        page.editFirstStatement?.should be_true
        page.firstClaimName.should == prop_a_label
        page.firstClaimValue.should == statement_value
      end
    end
    it "should check editing a statement" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.statementValueInput_element.clear
        page.statementValueInput = statement_value_changed
        page.saveStatement
        ajax_wait
        page.wait_for_statement_save_finished
        page.firstClaimName?.should be_true
        page.firstClaimValue?.should be_true
        page.addStatement?.should be_true
        page.saveStatement?.should be_false
        page.cancelStatement?.should be_false
        page.editFirstStatement?.should be_true
        page.firstClaimName.should == prop_a_label
        page.firstClaimValue.should == statement_value_changed
        @browser.refresh
        page.wait_for_entity_to_load
        page.addStatement?.should be_true
        page.editFirstStatement?.should be_true
        page.firstClaimName.should == prop_a_label
        page.firstClaimValue.should == statement_value_changed
      end
    end
  end

  after :all do
    # tear down
  end
end
