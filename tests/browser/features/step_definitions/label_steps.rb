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
  expect(on(ItemPage).edit_label_link?).to be true
end

Then /^Label edit button should not be there$/ do
  expect(on(ItemPage).edit_label_link?).to be false
end

Then /^Label input element should be there$/ do
  expect(on(ItemPage).label_input_field?).to be true
end

Then /^Label input element should not be there$/ do
  expect(on(ItemPage).label_input_field?).to be false
end

Then /^Label input element should contain original label$/ do
  expect(on(ItemPage).label_input_field).to be == @item_under_test["label"]
end

Then /^Label input element should be empty$/ do
  expect(on(ItemPage).label_input_field).to be == ""
end

Then /^Label cancel button should be there$/ do
  expect(on(ItemPage).cancel_label_link?).to be true
end

Then /^Label cancel button should not be there$/ do
  expect(on(ItemPage).cancel_label_link?).to be false
end

Then /^Label save button should be there$/ do
  expect(on(ItemPage).save_label_link?).to be true
end

Then /^Label save button should not be there$/ do
  expect(on(ItemPage).save_label_link?).to be false
end

Then /^Original label of item (.*) should be displayed$/ do |item_handle|
  expect(on(ItemPage).entity_label_span).to be == @items[item_handle]["label"]
end

Then /^Original label should be displayed$/ do
  on(ItemPage) do |page|
    expect(page.first_heading?).to be true
    expect(page.entity_label_span?).to be true
    expect(@browser.title.include?(@item_under_test["label"])).to be true
    expect(page.entity_label_span).to be == @item_under_test["label"]
  end
end

Then /^(.+) should be displayed as label$/ do |value|
  on(ItemPage) do |page|
    expect(page.first_heading?).to be true
    expect(page.entity_label_span?).to be true
    expect(@browser.title.include?(value)).to be true
    expect(page.entity_label_span).to be == value
  end
end

Then /^Entity id should be displayed next to the label$/ do
  on(ItemPage) do |page|
      expect(page.entity_id_span_element.visible?).to be true
      expect(page.entity_id_span.include?(@item_under_test["id"])).to be true
    end
end

Then /^Entity id should not be displayed next to the label$/ do
  expect(on(ItemPage).entity_id_span_element.visible?).to be false
end
