# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for statements

When(/^I have statements with the following properties and values:$/) do |statements|
  wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api

  as_user(:b) do
    wb_api.log_in(user(:b), password(:b))
  end

  statements.raw.each do |statement|
    property_handle = statement[0]
    value = on(ItemPage).get_string_snak_value(statement[1])
    wb_api.create_claim(@item_under_test['id'], 'value', @properties[property_handle]['id'], value)
  end
end

When /^I click the statement add button$/ do
  on(ItemPage).add_statement_element.when_visible.click
end

When /^I click the statement edit button$/ do
  on(ItemPage).edit_statement_element.when_visible.click
end

When /^I click the statement cancel button$/ do
  on(ItemPage).cancel_statement_element.when_visible.click
end

When(/^I click the statement save button$/) do
  on(ItemPage) do |page|
    page.save_statement_element.when_visible.click
    page.ajax_wait
    page.wait_for_statement_request_finished
  end
end

When(/^I select the claim property (.+)$/) do |handle|
  on(ItemPage) do |page|
    page.select_claim_property(@properties[handle]['label'])
    page.wait_for_claim_value_box
  end
end

When(/^I select the snak property (.+)$/) do |handle|
  on(ItemPage) do |page|
    page.select_snak_property(@properties[handle]['label'])
    page.wait_for_snak_value_box
  end
end

When(/^I enter (.+) in the claim property input field$/) do |value|
  on(ItemPage) do |page|
    page.claim_entity_selector_input_element.when_visible.click
    page.claim_entity_selector_input = value
    page.ajax_wait
  end
end

When(/^I enter (.+) in the claim value input field$/) do |value|
  on(ItemPage) do |page|
    page.claim_value_input_field_element.when_visible.click
    page.claim_value_input_field = value
    page.ajax_wait
  end
end

When(/^I enter (.+) in the InputExtender input field$/) do |value|
  on(ItemPage) do |page|
    page.inputextender_input_element.when_visible.click
    page.inputextender_input = value
    page.ajax_wait
  end
end

When(/^I press the ARROWDOWN key in the InputExtender input field$/) do
  on(ItemPage).inputextender_input_element.when_visible.send_keys :arrow_down
end

When(/^I press the RETURN key in the InputExtender input field$/) do
  on(ItemPage) do |page|
    page.inputextender_input_element.when_visible.send_keys :enter
    page.ajax_wait
    page.wait_for_statement_request_finished
  end
end

When(/^I click the InputExtender dropdown first element$/) do
  on(ItemPage).inputextender_dropdown_first_element.when_visible.click
  on(ItemPage).ajax_wait
end

When(/^I enter (.+) as string snak value$/) do |value|
  on(ItemPage) do |page|
    page.snak_value_input_field.when_visible.clear
    page.snak_value_input_field.when_visible.send_keys value
    page.ajax_wait
  end
end

When(/^I enter the label of item (.+) as claim value$/) do |handle|
  step "I enter #{@items[handle]['label']} in the claim value input field"
end

When(/^I enter a too long string as claim value$/) do
  step 'I enter looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong in the claim value input field'
end

When(/^I enter the label of item (.+) as snak value$/) do |handle|
  step "I enter #{@items[handle]['label']} as string snak value"
end

When(/^I enter a too long string as snak value$/) do
  step 'I enter looooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong as string snak value'
end

When(/^I press the ESC key in the claim value input field$/) do
  on(ItemPage).claim_value_input_field_element.when_visible.send_keys :escape
end

When(/^I press the ARROWDOWN key in the claim value input field$/) do
  on(ItemPage).claim_value_input_field_element.when_visible.send_keys :arrow_down
end

When(/^I press the ESC key in the snak value input field$/) do
  on(ItemPage).snak_value_input_field.when_visible.send_keys :escape
end

When(/^I press the RETURN key in the claim value input field$/) do
  on(ItemPage) do |page|
    page.claim_value_input_field_element.when_visible.send_keys :enter
    page.ajax_wait
    page.wait_for_statement_request_finished
  end
end

When(/^I press the RETURN key in the snak value input field$/) do
  on(ItemPage) do |page|
    page.snak_value_input_field.when_visible.send_keys :enter
    page.ajax_wait
    page.wait_for_statement_request_finished
  end
end

When(/^I edit claim (\d+) in group (\d+)$/) do |claim_index, group_index|
  on(ItemPage) do |page|
    page.edit_claim_element(group_index, claim_index).when_visible.click
    page.ajax_wait
  end
end

When(/^I memorize the value of the claim value input field$/) do
  @memorized_label = on(ItemPage).claim_value_input_field_element.when_visible.value
end

Then(/^Statements heading should be there$/) do
  expect(on(ItemPage).statements_heading_element.when_visible).to be_visible
end

Then(/^Statements heading should not be there$/) do
  expect(on(ItemPage).statements_heading_element.when_not_present).not_to be_present
end

Then(/^Statement help field should be there$/) do
  expect(on(ItemPage).statement_help_field_element.when_visible).to be_visible
end

Then(/^Statement add button should be there$/) do
  expect(on(ItemPage).add_statement_element.when_visible).to be_visible
end

Then(/^Statement add button should not be there$/) do
  expect(on(ItemPage).add_statement_element.when_not_present).not_to be_present
end

Then(/^Statement edit button for claim (.+) in group (.+) should be there$/) do |claim_index, group_index|
  expect(on(ItemPage).edit_claim_element(group_index, claim_index).when_present).to be_present
end

Then(/^Statement add button for group (.+) should be there$/) do |group_index|
  expect(on(ItemPage).add_claim_element(group_index).when_present).to be_present
end

Then(/^Statement save button should be there$/) do
  expect(on(ItemPage).save_statement_element.when_visible).to be_visible
end

Then(/^Statement save button should not be there$/) do
  expect(on(ItemPage).save_statement_element.when_not_present).not_to be_present
end

Then(/^Statement save button should be disabled$/) do
  on(ItemPage) do |page|
    expect(page.save_statement_element.when_not_present).not_to be_present
    expect(page.save_statement_disabled_element.when_visible).to be_visible
  end
end

Then(/^Statement cancel button should be there$/) do
  expect(on(ItemPage).cancel_statement_element.when_visible).to be_visible
end

Then(/^Statement cancel button should not be there$/) do
  expect(on(ItemPage).cancel_statement_element.when_not_present).not_to be_present
end

Then(/^Claim value input element should be there$/) do
  expect(on(ItemPage).claim_value_input_field_element.when_visible).to be_visible
end

Then(/^Claim value input element should not be there$/) do
  expect(on(ItemPage).claim_value_input_field_element.when_not_present).not_to be_present
end

Then(/^Snak value input element should be there$/) do
  expect(on(ItemPage).snak_value_input_field.when_visible).to be_visible
end

Then(/^Snak value input element should not be there$/) do
  expect(on(ItemPage).snak_value_input_field.when_not_present).not_to be_present
end

Then(/^Statement name of group (.+) should be the label of (.+)$/) do |group_index, handle|
  expect(on(ItemPage).statement_name_element(group_index).when_visible.text).to eq @properties[handle]['label']
end

Then(/^Statement string value of claim (.+) in group (.+) should be (.+)$/) do |claim_index, group_index, value|
  expect(on(ItemPage).claim_value_string(group_index, claim_index).when_visible.text).to eq value
end

Then(/^Statement value of claim (.+) in group (.+) should be the label of item (.+)$/) do |claim_index, group_index, handle|
  expect(on(ItemPage).claim_value_link(group_index, claim_index).when_visible.text).to eq @items[handle]['label']
end

Then(/^Statement value of claim (.+) in group (.+) should be what I memorized$/) do |claim_index, group_index|
  expect(on(ItemPage).claim_value_link(group_index, claim_index).when_visible.text).to eq @memorized_label
end

Then(/^Snaktype (.+) should be shown for claim (\d+) in group (\d+)$/) do |snaktype, claim_index, group_index|
  expect(on(ItemPage).claim_snaktype(group_index, claim_index, snaktype).when_present).to be_present
end

Then(/^Statement link element of claim (.+) in group (.+) should be there$/) do |claim_index, group_index|
  expect(on(ItemPage).claim_value_link(group_index, claim_index).when_present).to be_present
end

Then(/^Statement link text of claim (.+) in group (.+) should be (.+)$/) do |claim_index, group_index, value|
  expect(on(ItemPage).claim_value_link(group_index, claim_index).when_visible.text).to eq value
end

Then(/^Statement link url of claim (.+) in group (.+) should be (.+)$/) do |claim_index, group_index, value|
  expect(on(ItemPage).claim_value_link(group_index, claim_index).when_visible.attribute('href')).to eq value
end

Then(/^(.*) should be displayed in the InputExtender preview$/) do |preview|
  expect(on(ItemPage).inputextender_preview_element.when_present.text).to eq preview
end

Then(/^(.*) should be the time precision setting$/) do |precision|
  expect(on(ItemPage).time_precision_element.when_present.text).to eq precision
end

Then(/^(.*) should be the time calendar setting$/) do |calendar|
  expect(on(ItemPage).time_calendar_element.when_present.text).to eq calendar
end

Then(/^Time precision chooser should be there$/) do
  expect(on(ItemPage).time_precision_element.when_visible).to be_visible
end

Then(/^Time calendar chooser should be there$/) do
  expect(on(ItemPage).time_calendar_element.when_visible).to be_visible
end

Then(/^InputExtender preview should be there$/) do
  expect(on(ItemPage).inputextender_preview_element.when_visible).to be_visible
end

Then(/^InputExtender input should be there$/) do
  expect(on(ItemPage).inputextender_input_element.when_visible).to be_visible
end

Then(/^Geo precision chooser should be there$/) do
  expect(on(ItemPage).geo_precision_element.when_visible).to be_visible
end

Then(/^(.*) should be the geo precision setting$/) do |precision|
  expect(on(ItemPage).geo_precision_element.when_present.text).to eq precision
end

Then(/^InputExtender dropdown should be there$/) do
  expect(on(ItemPage).inputextender_dropdown_element.when_visible).to be_visible
end

Then(/^Unit suggester should be there$/) do
  expect(on(ItemPage).inputextender_unitsuggester_element.when_visible).to be_visible
end
