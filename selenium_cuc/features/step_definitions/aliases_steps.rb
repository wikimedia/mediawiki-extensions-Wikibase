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
  on(ItemPage) do |page|
    page.saveAliases
    page.wait_for_api_callback
  end
end

When /^I click the remove first alias button$/ do
  on(ItemPage).aliasesInputFirstRemove
end

When /^I press the ESC key in the new alias input field$/ do
  on(ItemPage).aliasesInputEmpty_element.send_keys :escape
end

When /^I press the RETURN key in the new alias input field$/ do
  on(ItemPage) do |page|
    page.aliasesInputEmpty_element.send_keys :return
    page.wait_for_api_callback
  end
end

When /^I enter (.+) as new aliases$/ do |values|
  on(ItemPage).insert_aliases(eval("[#{values}]"))
end

When /^I change the first alias to (.+)$/ do |value|
  on(ItemPage).aliasesInputFirst = value
end

When /^I change the duplicated alias to (.+)$/ do |value|
  on(ItemPage) do |page|
    page.aliasesInputEqual_element.clear
    page.aliasesInputEqual = value
  end
  sleep 10
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
  on(ItemPage).addAliases_element.visible?.should be_false
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

Then /^First remove alias button should be there$/ do
  on(ItemPage).aliasesInputFirstRemove?.should be_true
end

Then /^First remove alias button should not be there$/ do
  on(ItemPage).aliasesInputFirstRemove?.should be_false
end

Then /^New alias input field should be there$/ do
  on(ItemPage).aliasesInputEmpty?.should be_true
end

Then /^New alias input field should not be there$/ do
  on(ItemPage).aliasesInputEmpty?.should be_false
end

Then /^Modified alias input field should be there$/ do
  on(ItemPage).aliasesInputModified?.should be_true
end

Then /^Modified alias input field should not be there$/ do
  on(ItemPage).aliasesInputModified?.should be_false
end

Then /^Duplicate alias input field should be there$/ do
  on(ItemPage).aliasesInputEqual?.should be_true
end

Then /^Duplicate alias input field should not be there$/ do
  on(ItemPage).aliasesInputEqual?.should be_false
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

Then /^There should be (\d+) aliases in the list$/ do |num|
  on(ItemPage).count_existing_aliases.should == num.to_i
end

Then /^List of aliases should be (.+)$/ do |values|
  on(ItemPage).get_aliases.should == eval("[#{values}]")
end

Then /^First alias input field should contain (.+)$/ do |value|
  on(ItemPage).aliasesInputFirst.should == value
end
