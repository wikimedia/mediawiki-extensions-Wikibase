# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for statements

When /^I click the statement add button$/ do
  on(ItemPage).add_statement
end

When /^I click the statement cancel button$/ do
  on(ItemPage).cancel_statement
end

When /^I click the statement save button$/ do
  on(ItemPage) do |page|
    page.save_statement
    page.ajax_wait
    page.wait_for_statement_request_finished
  end
end

When /^I select the property (.+)$/ do |handle|
  on(ItemPage) do |page|
    page.select_entity(@properties[handle]["label"])
    page.wait_for_property_value_box
  end
end

When /^I enter (.+) in the property input field$/ do |value|
  on(ItemPage) do |page|
    page.entity_selector_input_element.clear
    page.entity_selector_input = value
    page.ajax_wait
  end
end

When /^I enter (.+) as string statement value$/ do |value|
  on(ItemPage) do |page|
    page.statement_value_input_field = value
    page.wait_for_save_button
  end
end

When /^I enter the label of the item with handle (.+) as statement value$/ do |handle|
  on(ItemPage) do |page|
    page.statement_value_input_field_element.clear
    page.statement_value_input_field = @items[handle]["label"]
    page.ajax_wait
  end
end

When /^I enter a too long string as statement value$/ do
  step 'I enter looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong as string statement value'
end

When /^I press the ESC key in the statement value input field$/ do
  on(ItemPage).statement_value_input_field_element.send_keys :escape
end

When /^I press the RETURN key in the statement value input field$/ do
  on(ItemPage) do |page|
    page.statement_value_input_field_element.send_keys :return
    puts "ret pressed"
    page.ajax_wait
    page.wait_for_statement_request_finished
  end
end

Then /^Statement help field should be there$/ do
  on(ItemPage).statement_help_field?.should be_true
end

Then /^Statement add button should be there$/ do
  on(ItemPage).add_statement?.should be_true
end

Then /^Statement add button should be disabled$/ do
  on(ItemPage) do |page|
    page.add_statement?.should be_false
    page.add_statement_disabled?.should be_true
  end
end

Then /^Statement edit button for claim (.+) in group (.+) should be there$/ do |claim_index, group_index|
  on(ItemPage).edit_claim_element(group_index, claim_index).exists?.should be_true
end

Then /^Statement save button should be there$/ do
  on(ItemPage).save_statement?.should be_true
end

Then /^Statement save button should not be there$/ do
  on(ItemPage).save_statement?.should be_false
end

Then /^Statement save button should be disabled$/ do
  on(ItemPage) do |page|
    page.save_statement?.should be_false
    page.save_statement_disabled?.should be_true
  end
end

Then /^Statement cancel button should be there$/ do
  on(ItemPage).cancel_statement?.should be_true
end

Then /^Statement cancel button should not be there$/ do
  on(ItemPage).cancel_statement?.should be_false
end

Then /^Statement value input element should be there$/ do
  on(ItemPage).statement_value_input_field?.should be_true
end

Then /^Statement value input element should not be there$/ do
  on(ItemPage).statement_value_input?.should be_false
end

Then /^Statement name of group (.+) should be the label of (.+)$/ do |group_index, handle|
  on(ItemPage).statement_name_element(group_index).text.should == @properties[handle]["label"]
end

Then /^Statement string value of claim (.+) in group (.+) should be (.+)$/ do |claim_index, group_index, value|
  on(ItemPage).statement_string_value_element(group_index, claim_index).attribute_value("value").should == value
end

Then /^Statement value of claim (.+) in group (.+) should be the label of the item with handle (.+)$/ do |claim_index, group_index, handle|
  on(ItemPage).statement_value_element(group_index, claim_index).text.should == @items[handle]["label"]
end
