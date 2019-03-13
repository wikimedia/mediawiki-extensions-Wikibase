# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# steps for item aliases

When(/^I empty the first alias$/) do
  on(ItemPage).aliases_input_first = ' ' # when using empty string change event is not fired
end

When(/^I press the ESC key in the new alias input field$/) do
  on(ItemPage).aliases_input_empty_element.send_keys :escape
end

When(/^I press the RETURN key in the new alias input field$/) do
  on(ItemPage) do |page|
    page.aliases_input_empty_element.send_keys :enter
    page.wait_for_api_callback
  end
end

When(/^I enter (.+) as new aliases$/) do |values|
  on(ItemPage).populate_aliases(eval("[#{values}]"))
end

When(/^I change the first alias to (.+)$/) do |value|
  # Assigning the value directly to the input somehow lead to the alias disappearing.
  # See https://phabricator.wikimedia.org/T218204

  on(ItemPage).aliases_input_first_element.double_click # to select all text
  on(ItemPage).aliases_input_first_element.send_keys value
end

Then(/^Aliases UI should be there$/) do
  on(ItemPage).aliases_div_element.when_visible
end

Then(/^New alias input field should be there$/) do
  expect(on(ItemPage).aliases_input_empty?).to be true
end

Then(/^New alias input field should not be there$/) do
  expect(on(ItemPage).aliases_input_empty?).to be false
end

Then(/^Modified alias input field should be there$/) do
  expect(on(ItemPage).aliases_input_modified?).to be true
end

Then(/^Modified alias input field should not be there$/) do
  expect(on(ItemPage).aliases_input_modified?).to be false
end

Then(/^Duplicate alias input field should be there$/) do
  expect(on(ItemPage).aliases_input_equal?).to be true
end

Then(/^Duplicate alias input field should not be there$/) do
  expect(on(ItemPage).aliases_input_equal?).to be false
end

Then(/^Aliases list should be empty$/) do
  expect(on(ItemPage).count_existing_aliases).to be == 0
end

Then(/^Aliases list should not be empty$/) do
  expect(on(ItemPage).count_existing_aliases).to be > 0
end

Then(/^Aliases help field should be there$/) do
  expect(on(ItemPage).aliases_help_field?).to be true
end

Then(/^There should be (\d+) aliases in the list$/) do |num|
  expect(on(ItemPage).count_existing_aliases).to be == num.to_i
end

Then(/^List of aliases should be (.+)$/) do |values|
  expect(on(ItemPage).aliases_array).to be == eval("[#{values}]")
end

Then(/^First alias input field should contain (.+)$/) do |value|
  expect(on(ItemPage).aliases_input_first).to be == value
end
