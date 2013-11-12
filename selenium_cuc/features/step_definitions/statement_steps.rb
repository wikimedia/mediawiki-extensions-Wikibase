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
  on(ItemPage).statementValueInput?.should be_true
end

Then /^Statement value input element should not be there$/ do
  on(ItemPage).statementValueInput?.should be_false
end
