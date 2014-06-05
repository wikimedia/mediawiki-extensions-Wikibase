# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# steps for item aliases

When /^I click the aliases add button$/ do
  on(ItemPage).add_aliases
end

When /^I click the aliases edit button$/ do
  on(ItemPage).edit_aliases
end

When /^I click the aliases cancel button$/ do
  on(ItemPage).cancel_aliases
end

When /^I click the aliases save button$/ do
  on(ItemPage) do |page|
    page.save_aliases
    page.wait_for_api_callback
  end
end

When /^I click the remove first alias button$/ do
  on(ItemPage).aliases_input_first_remove
end

When /^I press the ESC key in the new alias input field$/ do
  on(ItemPage).aliases_input_empty_element.send_keys :escape
end

When /^I press the RETURN key in the new alias input field$/ do
  on(ItemPage) do |page|
    page.aliases_input_empty_element.send_keys :return
    page.wait_for_api_callback
  end
end

When /^I enter (.+) as new aliases$/ do |values|
  on(ItemPage).populate_aliases(eval("[#{values}]"))
end

When /^I change the first alias to (.+)$/ do |value|
  on(ItemPage).aliases_input_first = value
end

When /^I change the duplicated alias to (.+)$/ do |value|
  on(ItemPage) do |page|
    page.aliases_input_equal_element.clear
    page.aliases_input_equal = value
  end
  sleep 10
end

Then /^Aliases UI should be there$/ do
  on(ItemPage) do |page|
    expect(page.aliases_div?).to be true
    expect(page.aliases_title?).to be true
  end
end

Then /^Aliases add button should be there$/ do
  expect(on(ItemPage).add_aliases?).to be true
end

Then /^Aliases add button should not be there$/ do
  expect(on(ItemPage).add_aliases_element.visible?).to be false
end

Then /^Aliases edit button should be there$/ do
  expect(on(ItemPage).edit_aliases?).to be true
end

Then /^Aliases edit button should not be there$/ do
  expect(on(ItemPage).edit_aliases?).to be false
end

Then /^Aliases cancel button should be there$/ do
  expect(on(ItemPage).cancel_aliases?).to be true
end

Then /^Aliases cancel button should not be there$/ do
  expect(on(ItemPage).cancel_aliases?).to be false
end

Then /^Aliases save button should be there$/ do
  expect(on(ItemPage).save_aliases?).to be true
end

Then /^Aliases save button should not be there$/ do
  expect(on(ItemPage).save_aliases?).to be false
end

Then /^Aliases save button should be disabled$/ do
  on(ItemPage) do |page|
    expect(page.save_aliases?).to be false
    expect(page.save_aliases_disabled?).to be true
  end
end

Then /^First remove alias button should be there$/ do
  expect(on(ItemPage).aliases_input_first_remove?).to be true
end

Then /^First remove alias button should not be there$/ do
  expect(on(ItemPage).aliases_input_first_remove?).to be false
end

Then /^New alias input field should be there$/ do
  expect(on(ItemPage).aliases_input_empty?).to be true
end

Then /^New alias input field should not be there$/ do
  expect(on(ItemPage).aliases_input_empty?).to be false
end

Then /^Modified alias input field should be there$/ do
  expect(on(ItemPage).aliases_input_modified?).to be true
end

Then /^Modified alias input field should not be there$/ do
  expect(on(ItemPage).aliases_input_modified?).to be false
end

Then /^Duplicate alias input field should be there$/ do
  expect(on(ItemPage).aliases_input_equal?).to be true
end

Then /^Duplicate alias input field should not be there$/ do
  expect(on(ItemPage).aliases_input_equal?).to be false
end

Then /^Aliases list should be empty$/ do
  expect(on(ItemPage).aliases_list?).to be false
end

Then /^Aliases list should not be empty$/ do
  expect(on(ItemPage).aliases_list?).to be true
end

Then /^Aliases help field should be there$/ do
  expect( on(ItemPage).aliases_help_field?).to be true
end

Then /^There should be (\d+) aliases in the list$/ do |num|
  expect(on(ItemPage).count_existing_aliases).to be == num.to_i
end

Then /^List of aliases should be (.+)$/ do |values|
  expect(on(ItemPage).get_aliases).to be == eval("[#{values}]")
end

Then /^First alias input field should contain (.+)$/ do |value|
  expect(on(ItemPage).aliases_input_first).to be == value
end
