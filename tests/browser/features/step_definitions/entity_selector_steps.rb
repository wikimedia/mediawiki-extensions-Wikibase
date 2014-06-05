# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for entity selector

When /^I press the ESC key in the entity selector input field$/ do
  on(ItemPage).entity_selector_input_element.send_keys :escape
end

Then /^Entity selector input element should be there$/ do
  expect(on(ItemPage).entity_selector_input?).to be true
end

Then /^Entity selector input element should not be there$/ do
  expect(on(ItemPage).entity_selector_input?).to be false
end
