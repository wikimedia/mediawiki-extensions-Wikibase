# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item description

When /^I click the description edit button$/ do
  on(ItemPage).editDescriptionLink
end

When /^I press the ESC key in the description input field$/ do
  on(ItemPage).descriptionInputField_element.send_keys :escape
end

When /^I press the RETURN key in the description input field$/ do
  on(ItemPage) do |page|
    page.descriptionInputField_element.send_keys :return
    page.wait_for_api_callback
  end
end

When /^I click the description cancel button$/ do
  on(ItemPage).cancelDescriptionLink
end

When /^I click the description save button$/ do
  on(ItemPage) do |page|
    page.saveDescriptionLink
    page.wait_for_api_callback
  end
end

When /^I enter (.+) as description$/ do |value|
  on(ItemPage) do |page|
    page.descriptionInputField_element.clear
    page.descriptionInputField = value
  end
end

Then /^Description edit button should be there$/ do
  on(ItemPage).editDescriptionLink?.should be_true
end

Then /^Description edit button should not be there$/ do
  on(ItemPage).editDescriptionLink?.should be_false
end

Then /^Description input element should be there$/ do
  on(ItemPage).descriptionInputField?.should be_true
end

Then /^Description input element should not be there$/ do
  on(ItemPage).descriptionInputField?.should be_false
end

Then /^Description input element should contain original description$/ do
  on(ItemPage).descriptionInputField.should == @entity["description"]
end

Then /^Description input element should be empty$/ do
  on(ItemPage).descriptionInputField.should == ""
end

Then /^Description cancel button should be there$/ do
  on(ItemPage).cancelDescriptionLink?.should be_true
end

Then /^Description cancel button should not be there$/ do
  on(ItemPage).cancelDescriptionLink?.should be_false
end

Then /^Description save button should be there$/ do
  on(ItemPage).saveDescriptionLink?.should be_true
end

Then /^Description save button should not be there$/ do
  on(ItemPage).saveDescriptionLink?.should be_false
end

Then /^Original description should be displayed$/ do
  on(ItemPage) do |page|
    page.firstHeading.should be_true
    page.entityDescriptionSpan.should be_true
    page.entityDescriptionSpan.should == @entity["description"]
  end
end

Then /^(.+) should be displayed as description$/ do |value|
  on(ItemPage) do |page|
    page.entityDescriptionSpan.should be_true
    page.entityDescriptionSpan.should == value
  end
end
