# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item label

When /^I click the label edit button$/ do
  on(ItemPage).editLabelLink
end

When /^I press the ESC key in the label input field$/ do
  on(ItemPage).labelInputField_element.send_keys :escape
end

When /^I press the RETURN key in the label input field$/ do
  on(ItemPage) do |page|
    page.labelInputField_element.send_keys :return
    page.wait_for_api_callback
  end
end

When /^I click the label cancel button$/ do
  on(ItemPage).cancelLabelLink
end

When /^I click the label save button$/ do
  on(ItemPage) do |page|
    page.saveLabelLink
    page.wait_for_api_callback
  end
end

When /^I enter (.+) as label$/ do |value|
  on(ItemPage) do |page|
    page.labelInputField_element.clear
    page.labelInputField = value
  end
end

Then /^Label edit button should be there$/ do
  on(ItemPage).editLabelLink?.should be_true
end

Then /^Label edit button should not be there$/ do
  on(ItemPage).editLabelLink?.should be_false
end

Then /^Label input element should be there$/ do
  on(ItemPage).labelInputField?.should be_true
end

Then /^Label input element should not be there$/ do
  on(ItemPage).labelInputField?.should be_false
end

Then /^Label input element should contain original label$/ do
  on(ItemPage).labelInputField.should == @entity["label"]
end

Then /^Label input element should be empty$/ do
  on(ItemPage).labelInputField.should == ""
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

Then /^Label save button should not be there$/ do
  on(ItemPage).saveLabelLink?.should be_false
end

Then /^Original label should be displayed$/ do
  on(ItemPage) do |page|
    page.firstHeading.should be_true
    page.entityLabelSpan.should be_true
    @browser.title.include?(@entity["label"]).should be_true
    page.entityLabelSpan.should == @entity["label"]
  end
end

Then /^(.+) should be displayed as label$/ do |value|
  on(ItemPage) do |page|
    page.firstHeading.should be_true
    page.entityLabelSpan.should be_true
    @browser.title.include?(value).should be_true
    page.entityLabelSpan.should == value
  end
end
