# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item description

When(/^I press the ESC key in the description input field$/) do
  on(ItemPage).description_input_field_element.send_keys :escape
end

When(/^I press the RETURN key in the description input field$/) do
  on(ItemPage) do |page|
    page.description_input_field_element.send_keys :enter
    page.wait_for_api_callback
  end
end

When(/^I enter "(.+)" as description$/) do |value|
  on(ItemPage) do |page|
    page.description_input_field_element.clear
    page.description_input_field = value
  end
end

When(/^I enter a long string as description$/) do
  step 'I enter "looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong" as description'
end

Then(/^Description input element should be there$/) do
  expect(on(ItemPage).description_input_field?).to be true
end

Then(/^Description input element should not be there$/) do
  expect(on(ItemPage).description_input_field?).to be false
end

Then(/^Description input element should contain original description$/) do
  expect(on(ItemPage).description_input_field).to be == @item_under_test['description']
end

Then(/^Description input element should be empty$/) do
  expect(on(ItemPage).description_input_field).to be == ''
end

Then(/^Description element should be there$/) do
  expect(on(ItemPage).entity_description_div?).to be true
end

Then(/^Original description should be displayed$/) do
  on(ItemPage) do |page|
    expect(page.first_heading?).to be true
    expect(page.entity_description_div?).to be true
    expect(page.entity_description_div).to be == @item_under_test['description']
  end
end

Then(/^"(.+)" should be displayed as description$/) do |value|
  on(ItemPage) do |page|
    expect(page.entity_description_div?).to be true
    expect(page.entity_description_div).to be == value
  end
end
