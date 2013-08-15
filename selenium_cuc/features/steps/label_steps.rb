# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item label

label = generate_random_string(10)
label_changed = label + " Adding something."

Given /^I am on an entity page$/ do
  item_data = '{"labels":{"en":{"language":"en","value":"' + label + '"}},"descriptions":{"en":{"language":"en","value":"' + generate_random_string(20) + '"}}}'
  item = create_new_entity(item_data, 'item')
  on(ItemPage).navigate_to_item item["url"]
end

When /^I click the label edit button$/ do
  on(ItemPage).editLabelLink
end

When /^I click the label cancel button$/ do
  on(ItemPage).cancelLabelLink
end

When /^I modify the label$/ do
  on(ItemPage) do |page|
    page.labelInputField_element.clear
    page.labelInputField = label_changed
  end
end

Then /^Label edit button should be there$/ do
  on(ItemPage).editLabelLink?.should be_true
end

Then /^Label edit button should not be there$/ do
  on(ItemPage).editLabelLink?.should be_true
end

Then /^Label input element should be there$/ do
  on(ItemPage).labelInputField?.should be_true
end

Then /^Label input element should not be there$/ do
  on(ItemPage).labelInputField?.should be_false
end

Then /^Label input element should contain original label$/ do
  on(ItemPage).labelInputField.should == label
end

Then /^Label cancel button should be there$/ do
  on(ItemPage).cancelLabelLink?.should be_true
end

Then /^Label cancel button should not be there$/ do
  on(ItemPage).cancelLabelLink?.should be_false
end

Then /^Label save button should be there$/ do
  on(ItemPage).saveLabelLink?.should be_true
end

Then /^Original label should be displayed$/ do
  on(ItemPage) do |page|
    page.firstHeading.should be_true
    page.entityLabelSpan.should be_true
    @browser.title.include?(label).should be_true
    page.entityLabelSpan.should == label
  end
end
