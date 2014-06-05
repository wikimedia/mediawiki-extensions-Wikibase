# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item sitelinks

When /^I click the sitelink add button$/ do
  on(ItemPage).add_sitelink_link
end

When /^I click the sitelink edit button$/ do
  on(ItemPage).edit_sitelink_link
end

When /^I click the sitelink cancel button$/ do
  on(ItemPage).cancel_sitelink_link
end

When /^I click the sitelink save button$/ do
  on(ItemPage) do |page|
    page.save_sitelink_link
    page.wait_for_api_callback
  end
end

When /^I press the ESC key in the siteid input field$/ do
  on(ItemPage).site_id_input_field_element.send_keys :escape
end

When /^I press the ESC key in the pagename input field$/ do
  on(ItemPage).page_input_field_element.send_keys :escape
end

When /^I press the RETURN key in the pagename input field$/ do
  on(ItemPage) do |page|
    page.page_input_field_element.send_keys :return
    page.wait_for_api_callback
  end
end

When /^I type (.+) into the siteid input field$/ do |value|
  on(ItemPage) do |page|
    page.site_id_input_field_element.clear
    page.site_id_input_field = value
  end
end

When /^I type (.+) into the page input field$/ do |value|
  on(ItemPage) do |page|
    page.page_input_field_element.clear
    page.page_input_field = value
    page.ajax_wait
  end
end

When /^I remove all sitelinks$/ do
  on(ItemPage).remove_all_sitelinks
end

When /^I add the following sitelinks:$/ do |table|
  on(ItemPage).add_sitelinks(table.raw)
end

When /^I order the sitelinks by languagename$/ do
  on(ItemPage).sitelink_sort_language_element.click
end

When /^I mock that the list of sitelinks is complete$/ do
  on(ItemPage).set_sitelink_list_to_full
end

Then /^Sitelink table should be there$/ do
  expect(on(ItemPage).sitelink_table?).to be true
end

Then /^Sitelink heading should be there$/ do
  expect(on(ItemPage).sitelink_heading?).to be true
end

Then /^Sitelink add button should be there$/ do
  expect(on(ItemPage).add_sitelink_link?).to be true
end

Then /^Sitelink add button should be disabled$/ do
  on(ItemPage) do |page|
    expect(page.add_sitelink_link?).to be false
    expect(page.add_sitelink_link_disabled?).to be true
  end
end

Then /^Sitelink edit button should be there$/ do
  expect(on(ItemPage).edit_sitelink_link?).to be true
end

Then /^Sitelink edit button should not be there$/ do
  expect(on(ItemPage).edit_sitelink_link?).to be false
end

Then /^Sitelink edit button should be disabled$/ do
  on(ItemPage) do |page|
    expect(page.edit_sitelink_link?).to be false
    expect(page.edit_sitelink_link_disabled?).to be true
  end
end

Then /^Sitelink save button should be there$/ do
  expect(on(ItemPage).save_sitelink_link?).to be true
end

Then /^Sitelink save button should not be there$/ do
  expect(on(ItemPage).save_sitelink_link?).to be false
end

Then /^Sitelink save button should be disabled$/ do
  on(ItemPage) do |page|
    expect(page.save_sitelink_link?).to be false
    expect(page.save_sitelink_link_disabled?).to be true
  end
end

Then /^Sitelink cancel button should be there$/ do
  expect(on(ItemPage).cancel_sitelink_link?).to be true
end

Then /^Sitelink cancel button should not be there$/ do
  expect(on(ItemPage).cancel_sitelink_link?).to be false
end

Then /^Sitelink counter should be there$/ do
  expect(on(ItemPage).sitelink_counter?).to be true
end

Then /^Sitelink counter should show (.+)$/ do |value|
  expect(on(ItemPage).get_number_of_sitelinks_from_counter).to be == value
end

Then /^There should be (\d+) sitelinks in the list$/ do |num|
  expect(on(ItemPage).count_existing_sitelinks).to be == num.to_i
end

Then /^Sitelink help field should be there$/ do
  expect(on(ItemPage).sitelink_help_field?).to be true
end

Then /^Sitelink siteid input field should be there$/ do
  expect(on(ItemPage).site_id_input_field?).to be true
end

Then /^Sitelink siteid input field should not be there$/ do
  expect(on(ItemPage).site_id_input_field?).to be false
end

Then /^Sitelink pagename input field should be there$/ do
  expect(on(ItemPage).page_input_field?).to be true
end

Then /^Sitelink pagename input field should be disabled$/ do
  expect(on(ItemPage).page_input_field_disabled?).to be true
end

Then /^Sitelink siteid dropdown should be there$/ do
  expect(on(ItemPage).site_id_dropdown_element.visible?).to be true
end

Then /^Sitelink siteid dropdown should not be there$/ do
  expect(on(ItemPage).site_id_dropdown_element.visible?).to be false
end

Then /^Sitelink siteid first suggestion should be (.+)$/ do |value|
  expect(on(ItemPage).site_id_dropdown_first_element).to be == value
end

Then /^Sitelink pagename dropdown should be there$/ do
  expect(on(ItemPage).page_name_dropdown_element.visible?).to be true
end

Then /^Sitelink pagename dropdown should not be there$/ do
  expect(on(ItemPage).page_name_dropdown_element.visible?).to be false
end

Then /^Sitelink pagename first suggestion should be (.+)$/ do |value|
  expect(on(ItemPage).page_name_dropdown_first_element).to be == value
end

Then /^Sitelink language table cell should contain (.+)$/ do |value|
  expect(on(ItemPage).sitelink_sitename).to be == value
end

Then /^Sitelink code table cell should contain (.+)$/ do |value|
  expect(on(ItemPage).sitelink_siteid).to be == value
end

Then /^Sitelink link text should be (.+)$/ do |value|
  expect(on(ItemPage).sitelink_link_element.text).to be == value
end

Then /^Sitelink link should lead to article (.+)$/ do |value|
  on(ItemPage) do |page|
    page.sitelink_link
    expect(page.article_title).to be == value
  end
end

Then /^Order of sitelinks should be:$/ do |siteids|
  expect(on(ItemPage).get_sitelinks_order).to be == siteids.raw[0]
end
