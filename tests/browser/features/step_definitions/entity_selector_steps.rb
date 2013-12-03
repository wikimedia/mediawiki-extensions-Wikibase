# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for entity selector

When /^I press the ESC key in the entity selector input field$/ do
  on(ItemPage).entitySelectorInput_element.send_keys :escape
end

Then /^Entity selector input element should be there$/ do
  on(ItemPage).entitySelectorInput?.should be_true
end

Then /^Entity selector input element should not be there$/ do
  on(ItemPage).entitySelectorInput?.should be_false
end
