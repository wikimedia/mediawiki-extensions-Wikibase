# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# steps for references

When(/^I click the reference add button$/) do
  on(ItemPage).add_reference_element.when_visible.click
end

When(/^I click the reference remove button$/) do
  on(ItemPage) do |page|
    page.remove_reference_element.when_visible.click
    page.ajax_wait
    page.wait_for_statement_request_finished
  end
end

When(/^I remove reference snak (\d+)$/) do |snak_index|
  on(ItemPage).remove_reference_snak(snak_index).when_visible.click
end

When /^I add the following reference snaks:$/ do |table|
  step 'I click the statement edit button'
  on(ItemPage) do |page|
    page.ajax_wait
    page.add_reference_snaks(table.raw, @properties)
  end
  step 'I click the statement save button'
end

When(/^I click the toggle references link of statement (\d+)$/) do |statement_index|
  on(ItemPage) do |page|
    page.toggle_references(statement_index).when_visible.click
    page.wait_until_jquery_animation_finished
  end
end

Then(/^Reference add button should be there$/) do
  expect(on(ItemPage).add_reference_element.when_visible).to be_visible
end

Then(/^Reference add button should not be there$/) do
  expect(on(ItemPage).add_reference_element.when_not_present).not_to be_present
end

Then(/^Reference add button should be disabled$/) do
  expect(on(ItemPage).add_reference_element.when_not_present).not_to be_present
  expect(on(ItemPage).add_reference_disabled_element.when_visible).to be_visible
end

Then(/^Reference remove button should be there$/) do
  expect(on(ItemPage).remove_reference_element.when_visible).to be_visible
end

Then(/^Reference remove button should not be there$/) do
  expect(on(ItemPage).remove_reference_element.when_not_present).not_to be_present
end

Then(/^Reference remove button should be disabled$/) do
  expect(on(ItemPage).remove_reference_element.when_not_present).not_to be_present
  expect(on(ItemPage).remove_reference_disabled_element.when_visible).to be_visible
end

Then(/^Reference remove snak button should be there$/) do
  expect(on(ItemPage).remove_reference_snak.when_present).to be_present
end

Then(/^Reference remove snak button should not be there$/) do
  expect(on(ItemPage).remove_reference_snak.when_not_present).not_to be_present
end

Then(/^Reference remove snak button should be disabled$/) do
  expect(on(ItemPage).remove_reference_snak.when_not_present).not_to be_present
  expect(on(ItemPage).remove_reference_snak_disabled.when_present).to be_present
end

Then(/^Reference add snak button should be there$/) do
  expect(on(ItemPage).add_reference_snak_element.when_visible).to be_visible
end

Then(/^Reference add snak button should not be there$/) do
  expect(on(ItemPage).add_reference_snak_element.when_not_present).not_to be_present
end

Then(/^Reference add snak button should be disabled$/) do
  expect(on(ItemPage).add_reference_snak_element.when_not_present).not_to be_present
  expect(on(ItemPage).add_reference_snak_disabled_element.when_visible).to be_visible
end

Then(/^Reference counter should be there$/) do
  expect(on(ItemPage).reference_counter_element.when_visible).to be_visible
end

Then(/^Reference counter should show (.+)$/) do |value|
  expect(on(ItemPage).number_of_references_from_counter).to be == value
end

Then(/^Property of snak (\d+) of reference (\d+) should be label of (.+)$/) do |snak_index, reference_index, property_handle|
  expect(on(ItemPage).reference_snak_property(reference_index, snak_index).when_visible.text).to be == @properties[property_handle]['label']
end

Then(/^Property of snak (\d+) of reference (\d+) should not be there$/) do |snak_index, reference_index|
  expect(on(ItemPage).reference_snak_property(reference_index, snak_index).when_not_present).not_to be_present
end

Then(/^Property of snak (\d+) of reference (\d+) should be linked$/) do |snak_index, reference_index|
  expect(on(ItemPage).reference_snak_property_link(reference_index, snak_index).when_present).to be_present
end

Then(/^Value of snak (\d+) of reference (\d+) should be (.+)$/) do |snak_index, reference_index, value|
  expect(on(ItemPage).reference_snak_value(reference_index, snak_index).when_visible.text).to be == value
end

Then(/^Value of snak (\d+) of reference (\d+) should not be there$/) do |snak_index, reference_index|
  expect(on(ItemPage).reference_snak_value(reference_index, snak_index).when_not_present).not_to be_present
end
