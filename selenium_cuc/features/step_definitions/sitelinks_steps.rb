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

When /^I click the sitelink cancel button$/ do
  on(ItemPage).cancelSitelinkLink
end

When /^I click the sitelink save button$/ do
  on(ItemPage) do |page|
    page.saveSitelinkLink
    page.wait_for_api_callback
  end
end

When /^I press the ESC key in the siteid input field$/ do
  on(ItemPage).siteIdInputField_element.send_keys :escape
end

When /^I press the ESC key in the pagename input field$/ do
  on(ItemPage).pageInputField_element.send_keys :escape
end

When /^I type (.+) into the siteid input field$/ do |value|
  on(ItemPage) do |page|
    page.siteIdInputField_element.clear
    page.siteIdInputField = value
  end
end

When /^I type (.+) into the page input field$/ do |value|
  on(ItemPage) do |page|
    page.pageInputField_element.clear
    page.pageInputField = value
    page.ajax_wait
  end
end

When /^I remove all sitelinks$/ do
  on(ItemPage).remove_all_sitelinks
end

When /^I add (.+) \/ (.+) as sitelinks$/ do |siteids, pagenames|
  on(ItemPage).add_sitelinks(eval("[#{siteids}]").zip eval("[#{pagenames}]"))
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

Then /^Sitelink edit button should be there$/ do
  on(ItemPage).editSitelinkLink?.should be_true
end

Then /^Sitelink edit button should be disabled$/ do
  on(ItemPage) do |page|
    page.editSitelinkLink?.should be_false
    page.editSitelinkLinkDisabled?.should be_true
  end
end

Then /^Sitelink save button should be there$/ do
  on(ItemPage).saveSitelinkLink?.should be_true
end

Then /^Sitelink save button should not be there$/ do
  on(ItemPage).saveSitelinkLink?.should be_false
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

Then /^Sitelink pagename input field should be there$/ do
  on(ItemPage).pageInputField?.should be_true
end

Then /^Sitelink pagename input field should be disabled$/ do
  on(ItemPage).pageInputFieldDisabled?.should be_true
end

Then /^Sitelink siteid dropdown should be there$/ do
  on(ItemPage).siteIdDropdown_element.visible?.should be_true
end

Then /^Sitelink siteid dropdown should not be there$/ do
  on(ItemPage).siteIdDropdown_element.visible?.should be_false
end

Then /^Sitelink siteid first suggestion should be (.+)$/ do |value|
  on(ItemPage).siteIdDropdownFirstElement.should == value
end

Then /^Sitelink pagename dropdown should be there$/ do
  on(ItemPage).pageNameDropdown_element.visible?.should be_true
end

Then /^Sitelink pagename dropdown should not be there$/ do
  on(ItemPage).pageNameDropdown_element.visible?.should be_false
end

Then /^Sitelink pagename first suggestion should be (.+)$/ do |value|
  on(ItemPage).pageNameDropdownFirstElement.should == value
end

Then /^Sitelink language table cell should contain (.+)$/ do |value|
  on(ItemPage).sitelinkSitename.should == value
end

Then /^Sitelink code table cell should contain (.+)$/ do |value|
  on(ItemPage).sitelinkSiteid.should == value
end

Then /^Sitelink link text should be (.+)$/ do |value|
  on(ItemPage).sitelinkLink_element.text.should == value
end

Then /^Sitelink link should lead to article (.+)$/ do |value|
  on(ItemPage) do |page|
    page.sitelinkLink
    page.articleTitle.should == value
  end
end
