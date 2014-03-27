# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item label

When /^I click the label edit button$/ do
  on(ItemPage).edit_label_link
end

When /^I press the ESC key in the label input field$/ do
  on(ItemPage).label_input_field_element.send_keys :escape
end

When /^I press the RETURN key in the label input field$/ do
  on(ItemPage) do |page|
    page.label_input_field_element.send_keys :return
    page.wait_for_api_callback
  end
end

When /^I click the label cancel button$/ do
  on(ItemPage).cancel_label_link
end

When /^I click the label save button$/ do
  on(ItemPage) do |page|
    page.save_label_link
    page.wait_for_api_callback
  end
end

When /^I enter "(.+)" as label$/ do |value|
  on(ItemPage) do |page|
    page.label_input_field_element.clear
    page.label_input_field = value
  end
end

When /^I enter a long string as label$/ do
  step 'I enter "looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong" as label'
end

Then /^Label edit button should be there$/ do
  on(ItemPage).edit_label_link?.should be_true
end

Then /^Label edit button should not be there$/ do
  on(ItemPage).edit_label_link?.should be_false
end

Then /^Label input element should be there$/ do
  on(ItemPage).label_input_field?.should be_true
end

Then /^Label input element should not be there$/ do
  on(ItemPage).label_input_field?.should be_false
end

Then /^Label input element should contain original label$/ do
  on(ItemPage).label_input_field.should == @item_under_test["label"]
end

Then /^Label input element should be empty$/ do
  on(ItemPage).label_input_field.should == ""
end

Then /^Label cancel button should be there$/ do
  on(ItemPage).cancel_label_link?.should be_true
end

Then /^Label cancel button should not be there$/ do
  on(ItemPage).cancel_label_link?.should be_false
end

Then /^Label save button should be there$/ do
  on(ItemPage).save_label_link?.should be_true
end

Then /^Label save button should not be there$/ do
  on(ItemPage).save_label_link?.should be_false
end

Then /^Original label of item (.*) should be displayed$/ do |item_handle|
  on(ItemPage).entity_label_span.should == @items[item_handle]["label"]
end

Then /^Original label should be displayed$/ do
  on(ItemPage) do |page|
    page.first_heading.should be_true
    page.entity_label_span.should be_true
    @browser.title.include?(@item_under_test["label"]).should be_true
    page.entity_label_span.should == @item_under_test["label"]
  end
end

Then /^(.+) should be displayed as label$/ do |value|
  on(ItemPage) do |page|
    page.first_heading.should be_true
    page.entity_label_span.should be_true
    @browser.title.include?(value).should be_true
    page.entity_label_span.should == value
  end
end

Then /^Entity id should be displayed next to the label$/ do
  on(ItemPage) do |page|
      page.entity_id_span_element.visible?.should be_true
      page.entity_id_span.sub(/[()]/, "") == @item_under_test["label"]
    end
end

Then /^Entity id should not be displayed next to the label$/ do
  on(ItemPage).entity_id_span_element.visible?.should be_false
end
