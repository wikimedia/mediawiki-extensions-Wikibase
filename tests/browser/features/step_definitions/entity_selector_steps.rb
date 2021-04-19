# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for entity selector

When(/^I press the ESC key in the snak entity selector input field$/) do
  on(ItemPage).snak_entity_selector_input.when_visible.send_keys :escape
end

When(/^I close the entity selector popup if present$/) do
  on(ItemPage) do |page|
    page.ajax_wait
    if page.entity_selector_list.present?
      page.claim_entity_selector_input_element.when_visible.send_keys :escape
    end
  end
end

When(/^I press the ESC key in the claim entity selector input field$/) do
  on(ItemPage).claim_entity_selector_input_element.when_visible.send_keys :escape
end

Then(/^Snak entity selector input element should be there$/) do
  expect(on(ItemPage).snak_entity_selector_input.when_visible).to be_visible
end

Then(/^Snak entity selector input element should not be there$/) do
  expect(on(ItemPage).snak_entity_selector_input.when_not_present).not_to be_present
end

Then(/^Claim entity selector input element should be there$/) do
  expect(on(ItemPage).claim_entity_selector_input_element.when_visible).to  be_visible
end

Then(/^Claim entity selector input element should not be there$/) do
  expect(on(ItemPage).claim_entity_selector_input_element.when_not_present).not_to be_present
end
