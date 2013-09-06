# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# steps for item aliases

When /^I click the aliases add button$/ do
  on(ItemPage).addAliases
end

When /^I click the aliases edit button$/ do
  on(ItemPage).editAliases
end

When /^I click the aliases cancel button$/ do
  on(ItemPage).cancelAliases
end

When /^I click the aliases save button$/ do
  on(ItemPage).saveAliases
end

When /^I press the ESC key in the new alias input field$/ do
  on(ItemPage).aliasesInputEmpty_element.send_keys :escape
end

Then /^Aliases UI should be there$/ do
  on(ItemPage) do |page|
    page.aliasesDiv?.should be_true
    page.aliasesTitle?.should be_true
  end
end

Then /^Aliases add button should be there$/ do
  on(ItemPage).addAliases?.should be_true
end

Then /^Aliases add button should not be there$/ do
  on(ItemPage).addAliases?.should be_false
end

Then /^Aliases edit button should be there$/ do
  on(ItemPage).editAliases?.should be_true
end

Then /^Aliases edit button should not be there$/ do
  on(ItemPage).editAliases?.should be_false
end

Then /^Aliases cancel button should be there$/ do
  on(ItemPage).cancelAliases?.should be_true
end

Then /^Aliases cancel button should not be there$/ do
  on(ItemPage).cancelAliases?.should be_false
end

Then /^Aliases save button should be there$/ do
  on(ItemPage).saveAliases?.should be_true
end

Then /^Aliases save button should not be there$/ do
  on(ItemPage).saveAliases?.should be_false
end

Then /^Aliases save button should be disabled$/ do
  on(ItemPage) do |page|
    page.saveAliases?.should be_false
    page.saveAliasesDisabled?.should be_true
  end
end

Then /^New alias input field should be there$/ do
  on(ItemPage).aliasesInputEmpty?.should be_true
end

Then /^New alias input field should not be there$/ do
  on(ItemPage).aliasesInputEmpty?.should be_false
end

Then /^Aliases list should be empty$/ do
  on(ItemPage).aliasesList?.should be_false
end

Then /^Aliases list should not be empty$/ do
  on(ItemPage).aliasesList?.should be_true
end

Then /^Aliases help field should be there$/ do
  on(ItemPage).aliasesHelpField?.should be_true
end

When /^I enter (.+) as new alias$/ do |value|
  on(ItemPage).aliasesInputEmpty = value
end