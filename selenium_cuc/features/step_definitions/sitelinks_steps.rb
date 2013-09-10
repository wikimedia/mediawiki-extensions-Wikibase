# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item sitelinks

When /^I click the sitelink add button$/ do
  on(ItemPage).addSitelinkLink
end

Then /^Sitelink table should be there$/ do
  on(ItemPage).sitelinkTable?.should be_true
end

Then /^Sitelink heading should be there$/ do
  on(ItemPage).sitelinkHeading?.should be_true
end

Then /^Sitelink add button should be there$/ do
  on(ItemPage).addSitelinkLink?.should be_true
end

Then /^Sitelink add button should be disabled$/ do
  on(ItemPage) do |page|
    page.addSitelinkLink?.should be_false
    page.addSitelinkLinkDisabled?.should be_true
  end
end

Then /^Sitelink save button should be there$/ do
  on(ItemPage).saveSitelinkLink?.should be_true
end

Then /^Sitelink save button should be disabled$/ do
  on(ItemPage) do |page|
    page.saveSitelinkLink?.should be_false
    page.saveSitelinkLinkDisabled?.should be_true
  end
end

Then /^Sitelink cancel button should be there$/ do
  on(ItemPage).cancelSitelinkLink?.should be_true
end

Then /^Sitelink cancel button should not be there$/ do
  on(ItemPage).cancelSitelinkLink?.should be_false
end

Then /^Sitelink counter should be there$/ do
  on(ItemPage).sitelinkCounter?.should be_true
end

Then /^Sitelink counter should show (.+)$/ do |value|
  on(ItemPage).get_number_of_sitelinks_from_counter.should == value
end

Then /^There should be (\d+) sitelinks in the list$/ do |num|
  on(ItemPage).count_existing_sitelinks.should == num.to_i
end

Then /^Sitelink help field should be there$/ do
  on(ItemPage).sitelinkHelpField?.should be_true
end

Then /^Sitelink siteid input field should be there$/ do
  on(ItemPage).siteIdInputField?.should be_true
end

Then /^Sitelink siteid input field should not be there$/ do
  on(ItemPage).siteIdInputField?.should be_false
end

Then /^Sitelink page input field should be there$/ do
  on(ItemPage).pageInputField?.should be_true
end

Then /^Sitelink page input field should be disabled$/ do
  on(ItemPage).pageInputFieldDisabled?.should be_true
end
