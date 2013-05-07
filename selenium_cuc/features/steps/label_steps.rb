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
  visit_page(CreateItemPage) do |page|
    page.create_new_item(label, generate_random_string(20))
  end
end

When /^I click the label edit button$/ do
  on_page(ItemPage) do |page|
    page.editLabelLink
  end
end

When /^I click the label cancel button$/ do
  on_page(ItemPage) do |page|
    page.cancelLabelLink
  end
end

When /^I modify the label$/ do
  on_page(ItemPage) do |page|
    page.labelInputField_element.clear
    page.labelInputField = label_changed
  end
end

Then /^Label edit button should be there$/ do
  on_page(ItemPage) do |page|
    page.editLabelLink?.should be_true
  end
end

Then /^Label edit button should not be there$/ do
  on_page(ItemPage) do |page|
    page.editLabelLink?.should be_true
  end
end

Then /^Label input element should be there$/ do
  on_page(ItemPage) do |page|
    page.labelInputField?.should be_true
  end
end

Then /^Label input element should not be there$/ do
  on_page(ItemPage) do |page|
    page.labelInputField?.should be_false
  end
end

Then /^Label input element should contain original label$/ do
  on_page(ItemPage) do |page|
    page.labelInputField.should == label
  end
end

Then /^Label cancel button should be there$/ do
  on_page(ItemPage) do |page|
    page.cancelLabelLink?.should be_true
  end
end

Then /^Label cancel button should not be there$/ do
  on_page(ItemPage) do |page|
    page.cancelLabelLink?.should be_false
  end
end

Then /^Label save button should be there$/ do
  on_page(ItemPage) do |page|
    page.saveLabelLink?.should be_true
  end
end

Then /^Original label should be displayed$/ do
  on_page(ItemPage) do |page|
    page.firstHeading.should be_true
    page.entityLabelSpan.should be_true
    @browser.title.include?(label).should be_true
    page.entityLabelSpan.should == label
  end
end
