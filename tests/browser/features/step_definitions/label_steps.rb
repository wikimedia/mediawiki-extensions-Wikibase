# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item label

When(/^I press the ESC key in the label input field$/) do
  on(ItemPage).label_input_field_element.send_keys :escape
end

When(/^I press the RETURN key in the label input field$/) do
  on(ItemPage) do |page|
    page.label_input_field_element.send_keys :enter
    page.wait_for_api_callback
  end
end

When(/^I enter "(.+)" as label$/) do |value|
  on(ItemPage) do |page|
    page.label_input_field_element.when_visible.clear
    page.label_input_field = value
  end
end

When(/^I enter random string as label$/) do
  @random_string = generate_random_string(14)
  step "I enter \"#{@random_string}\" as label"
end

When(/^I enter a long string as label$/) do
  step 'I enter "looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong" as label'
end

Then(/^Label input element should be there$/) do
  expect(on(ItemPage).label_input_field?).to be true
end

Then(/^Label input element should not be there$/) do
  expect(on(ItemPage).label_input_field?).to be false
end

Then(/^Label input element should contain original label$/) do
  expect(on(ItemPage).label_input_field).to eq @item_under_test['label']
end

Then(/^Label input element should be empty$/) do
  expect(on(ItemPage).label_input_field).to eq ''
end

Then(/^Original label of item (.*) should be displayed$/) do |item_handle|
  expect(on(ItemPage).entity_label_span).to eq @items[item_handle]['label']
end

Then(/^Original label should be displayed$/) do
  on(ItemPage) do |page|
    expect(page.first_heading?).to be true
    page.wait_for_label(@item_under_test['label'])
    expect(page.entity_label_span).to eq @item_under_test['label']
  end
end

Then(/^Label element should be there$/) do
  expect(on(ItemPage).first_heading?).to be true
end

Then(/^"(.+)" should be displayed as English label in the EntityTermView box$/) do |value|
  on(ItemPage) do |page|
    expect(page.en_terms_view_label_element.when_visible.text).to eq value
  end
end

Then(/^random string should be displayed as English label in the EntityTermView box$/) do
  step "\"#{@random_string}\" should be displayed as English label in the EntityTermView box"
end

Then(/^"(.+)" should be displayed as label$/) do |value|
  on(ItemPage) do |page|
    expect(page.first_heading?).to be true
    page.wait_for_label(value)
    expect(page.entity_label_span).to eq value
  end
end

Then(/^random string should be displayed as label$/) do
  step "\"#{@random_string}\" should be displayed as label"
end

Then(/^(.+) should be displayed as label having the ID of (.+)$/) do |value, item_handle|
  on(ItemPage) do |page|
    expect(page.first_heading?).to be true
    page.wait_for_label(value)
    expect(page.entity_label_span).to eq value
    expect(page.entity_id_span).to eq "(#{@items[item_handle]['id']})"
  end
end

Then(/^(.+) should be displayed as entity id next to the label$/) do |entity_id|
  on(ItemPage) do |page|
    expect(page.entity_id_span_element.when_visible.text).to eq "(#{entity_id})"
  end
end
