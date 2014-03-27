# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Mättig (thiemo.maettig@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for the Special:SetLabel page

When /^I am on the Special:SetLabel page$/ do
  visit(SpecialSetLabelPage)
end

When /^I enter (.+) into the label input field$/ do |label|
  on(SpecialSetLabelPage).term_input_field = label
end

When /^I press the set label button$/ do
  on(SpecialSetLabelPage).set_label_button
end

Then /^Label input field should be there$/ do
  on(SpecialSetLabelPage).term_input_field?.should be_true
end

Then /^Set label button should be there$/ do
  on(SpecialSetLabelPage).set_label_button?.should be_true
end