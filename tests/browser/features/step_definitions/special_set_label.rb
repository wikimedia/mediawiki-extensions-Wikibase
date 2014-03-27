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

When /^I enter the item ID into the ID input field$/ do
  on(SpecialSetLabelPage).id_input_field = @item_under_test['id']
end

When /^I enter (.*) into the language input field$/ do |language_code|
  on(SpecialSetLabelPage).language_input_field = language_code
end

When /^I enter (.*) into the label input field$/ do |label|
  on(SpecialSetLabelPage).label_input_field = label
end

When /^I press the set label button$/ do
  on(SpecialSetLabelPage).set_label_button
end
