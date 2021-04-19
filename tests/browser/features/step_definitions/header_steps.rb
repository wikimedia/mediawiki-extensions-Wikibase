# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# Author:: Christoph Fischer (christoph.fischer@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for the header section

When(/^I click the header edit button$/) do
  on(ItemPage).edit_header_link_element.when_visible.click
end

When(/^I click the header cancel button$/) do
  on(ItemPage).cancel_header_link_element.when_visible.click
end

When(/^I click the header save button$/) do
  on(ItemPage) do |page|
    page.save_header_link_element.when_visible.click
    page.wait_for_api_callback
  end
end

When(/^I click the EntityTermsView toggler$/) do
  on(ItemPage).terms_view_toggler_element.when_visible.click
end

Then(/^Header edit button should be there$/) do
  expect(on(ItemPage).edit_header_link_element.when_visible).to be_visible
end

Then(/^Header edit button should not be there$/) do
  expect(on(ItemPage).edit_header_link_element.when_not_present).not_to be_present
end

Then(/^Header cancel button should be there$/) do
  expect(on(ItemPage).cancel_header_link_element.when_visible).to be_visible
end

Then(/^Header cancel button should not be there$/) do
  expect(on(ItemPage).cancel_header_link_element.when_not_present).not_to be_present
end

Then(/^Header save button should be there$/) do
  expect(on(ItemPage).save_header_link_element.when_visible).to be_visible
end

Then(/^Header save button should not be there$/) do
  expect(on(ItemPage).save_header_link_element.when_not_present).not_to be_present
end
