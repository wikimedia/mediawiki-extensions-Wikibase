# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item description

When /^I click the description edit button$/ do
  on(ItemPage).edit_description_link
end

When /^I press the ESC key in the description input field$/ do
  on(ItemPage).description_input_field_element.send_keys :escape
end

When /^I press the RETURN key in the description input field$/ do
  on(ItemPage) do |page|
    page.description_input_field_element.send_keys :return
    page.wait_for_api_callback
  end
end

When /^I click the description cancel button$/ do
  on(ItemPage).cancel_description_link
end

When /^I click the description save button$/ do
  on(ItemPage) do |page|
    page.save_description_link
    page.wait_for_api_callback
  end
end

When /^I enter "(.+)" as description$/ do |value|
  on(ItemPage) do |page|
    page.description_input_field_element.clear
    page.description_input_field = value
  end
end

When /^I enter a long string as description$/ do
  step "I enter \"looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong\" as description"
end

Then /^Description edit button should be there$/ do
  on(ItemPage).edit_description_link?.should be_true
end

Then /^Description edit button should not be there$/ do
  on(ItemPage).edit_description_link?.should be_false
end

Then /^Description input element should be there$/ do
  on(ItemPage).description_input_field?.should be_true
end

Then /^Description input element should not be there$/ do
  on(ItemPage).description_input_field?.should be_false
end

Then /^Description input element should contain original description$/ do
  on(ItemPage).description_input_field.should == @item_under_test["description"]
end

Then /^Description input element should be empty$/ do
  on(ItemPage).description_input_field.should == ""
end

Then /^Description cancel button should be there$/ do
  on(ItemPage).cancel_description_link?.should be_true
end

Then /^Description cancel button should not be there$/ do
  on(ItemPage).cancel_description_link?.should be_false
end

Then /^Description save button should be there$/ do
  on(ItemPage).save_description_link?.should be_true
end

Then /^Description save button should not be there$/ do
  on(ItemPage).save_description_link?.should be_false
end

Then /^Original description should be displayed$/ do
  on(ItemPage) do |page|
    page.first_heading.should be_true
    page.entity_description_span.should be_true
    page.entity_description_span.should == @item_under_test["description"]
  end
end

Then /^(.+) should be displayed as description$/ do |value|
  on(ItemPage) do |page|
    page.entity_description_span.should be_true
    page.entity_description_span.should == value
  end
end
