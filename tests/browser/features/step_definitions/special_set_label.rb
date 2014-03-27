# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Mättig
# License:: GNU GPL v2+
#
# tests for set label special page

When /^I am on the set label special page$/ do
  visit(SpecialSetLabelPage)
end

Then /^Anonymous edit warning should be there$/ do
  on(SpecialSetLabelPage).anonymous_edit_warning?.should be_true
end

Then /^Anonymous edit warning should not be there$/ do
  on(SpecialSetLabelPage).anonymous_edit_warning?.should be_false
end

Then /^ID input field should be there$/ do
  on(SpecialSetLabelPage).id_input_field?.should be_true
end

Then /^Language input field should be there$/ do
  on(SpecialSetLabelPage).language_input_field?.should be_true
end

Then /^Label input field should be there$/ do
  on(SpecialSetLabelPage).label_input_field?.should be_true
end

Then /^Set label button should be there$/ do
  on(SpecialSetLabelPage).set_label_button?.should be_true
end
