# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for string type statements

require 'spec_helper'

item_label = generate_random_string(10)
item_description = generate_random_string(20)
prop_label = generate_random_string(10)
prop_description = generate_random_string(20)
prop_datatype = "String"
string_value = generate_random_string(50)
string_value_toolong = generate_random_string(401)
string_value_evil = "<script>$('body').empty();</script>"

describe "Check string statements UI" do
  before :all do
    # set up: create item & properties
    visit_page(CreateItemPage) do |page|
      page.create_new_item(item_label, item_description)
    end
    visit_page(NewPropertyPage) do |page|
      page.create_new_property(prop_label, prop_description, prop_datatype)
    end
  end

  context "Check statements UI" do
    it "should check adding a statement of string type" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_statement(prop_label, string_value)
        page.statement1Name.should == prop_label
        # TODO: this is a workaround, as the textarea is currently not holding the actual string value (it's empty)
        page.editFirstStatement
        page.statementValueInput.should == string_value
        page.cancelStatement
        @browser.refresh
        page.wait_for_entity_to_load
        page.statement1Name.should == prop_label
        # TODO: see above
        page.editFirstStatement
        page.statementValueInput.should == string_value
        page.cancelStatement
        page.remove_all_claims
      end
    end
    it "should check adding adding a statement with an evil string (JS injection)" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_statement(prop_label, string_value_evil)
        page.firstHeading?.should be_true
        page.statement1Name.should == prop_label
        # TODO: see above
        page.editFirstStatement
        page.statementValueInput.should == string_value_evil
        page.cancelStatement
        @browser.refresh
        page.wait_for_entity_to_load
        page.firstHeading?.should be_true
        page.statement1Name.should == prop_label
        # TODO: see above
        page.editFirstStatement
        page.statementValueInput.should == string_value_evil
        page.cancelStatement
        page.remove_all_claims
      end
    end
    it "should check adding a statement of string type with too long string" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.entitySelectorInput = prop_label
        ajax_wait
        page.wait_for_entity_selector_list
        page.wait_for_property_value_box
        page.statementValueInput = string_value_toolong
        page.saveStatement
        ajax_wait
        page.wbErrorDiv?.should be_true
        page.cancelStatement
      end
    end
  end

  after :all do
    # tear down
  end

end