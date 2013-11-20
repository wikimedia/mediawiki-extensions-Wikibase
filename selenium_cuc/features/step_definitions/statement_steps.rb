# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for statements

When /^I click the statement add button$/ do
  on(ItemPage).addStatement
end

When /^I click the statement cancel button$/ do
  on(ItemPage).cancelStatement
end

When /^I select the property (.+)$/ do |handle|
  on(ItemPage) do |page|
    page.select_entity(@properties[handle]["label"])
    page.wait_for_property_value_box
  end
end

When /^I enter (.+) in the property input field$/ do |value|
  on(ItemPage) do |page|
    page.entitySelectorInput_element.clear
    page.entitySelectorInput = value
    page.ajax_wait
  end
end

When /^I enter (.+) as string statement value$/ do |value|
  on(ItemPage).statementValueInputField = value
end

When /^I press the ESC key in the statement value input field$/ do
  on(ItemPage).statementValueInputField_element.send_keys :escape
end

Then /^Statement help field should be there$/ do
  on(ItemPage).statementHelpField?.should be_true
end

Then /^Statement add button should be there$/ do
  on(ItemPage).addStatement?.should be_true
end

Then /^Statement add button should be disabled$/ do
  on(ItemPage) do |page|
    page.addStatement?.should be_false
    page.addStatementDisabled?.should be_true
  end
end

Then /^Statement save button should be there$/ do
  on(ItemPage).saveStatement?.should be_true
end

Then /^Statement save button should not be there$/ do
  on(ItemPage).saveStatement?.should be_false
end

Then /^Statement save button should be disabled$/ do
  on(ItemPage) do |page|
    page.saveStatement?.should be_false
    page.saveStatementDisabled?.should be_true
  end
end

Then /^Statement cancel button should be there$/ do
  on(ItemPage).cancelStatement?.should be_true
end

Then /^Statement cancel button should not be there$/ do
  on(ItemPage).cancelStatement?.should be_false
end

Then /^Statement value input element should be there$/ do
  on(ItemPage).statementValueInputField?.should be_true
end

Then /^Statement value input element should not be there$/ do
  on(ItemPage).statementValueInput?.should be_false
end
