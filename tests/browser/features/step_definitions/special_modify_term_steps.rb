# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Kreuz
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for the Special:SetLabel page

Then(/^Language input field should be there$/) do
  expect(on(SpecialModifyTermPage).language_input_field?).to be true
end

When(/^I enter (.+) into the language input field$/) do |language_code|
  on(SpecialModifyTermPage).language_input_field = language_code
end
