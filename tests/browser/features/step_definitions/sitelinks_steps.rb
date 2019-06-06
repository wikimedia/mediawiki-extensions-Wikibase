# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item sitelinks

When(/^I click the sitelink remove button$/) do
  on(ItemPage).remove_sitelink_link_element.when_visible.click
end

When(/^I click the sitelink edit button$/) do
  on(ItemPage).edit_sitelink_link_element.when_visible.click
end

When(/^I click the sitelink cancel button$/) do
  on(ItemPage).cancel_sitelink_link_element.when_visible.click
end

When(/^I click the sitelink save button$/) do
  on(ItemPage) do |page|
    page.save_sitelink_link_element.when_present.click
    page.wait_for_api_callback
  end
end

When(/^I press the ESC key in the siteid input field$/) do
  on(ItemPage).sitelinks_form[1].site_id_input_field_element.when_visible.send_keys :escape
end

When(/^I press the ESC key in the pagename input field$/) do
  on(ItemPage).sitelinks_form[1].page_input_field_element.when_visible.send_keys :escape
end

When(/^I press the RETURN key in the pagename input field$/) do
  on(ItemPage) do |page|
    page.sitelinks_form[1].page_input_field_element.when_visible.send_keys :enter
    page.wait_for_api_callback
  end
end

When(/^I type (.+) into the (\d+). siteid input field$/) do |site, index|
  on(ItemPage).insert_site(index, site)
end

When(/^I type (.+) into the (\d+). page input field$/) do |page, index|
  on(ItemPage).insert_page(index, page)
end

When(/^I remove all sitelinks$/) do
  on(ItemPage).remove_all_sitelinks
end

When(/^I add the following sitelinks:$/) do |table|
  on(ItemPage).add_sitelinks(table.raw)
end

When(/^I order the sitelinks by languagename$/) do
  on(ItemPage).sitelink_sort_language_element.when_visible.click
end

When(/^I mock that the list of sitelinks is complete$/) do
  on(ItemPage).set_sitelink_list_to_full
end

Then(/^(.+) sitelink section should be there$/) do |section|
  on(ItemPage).sitelinks_sections[section].section_div_element.when_visible
end

Then(/^Sitelink heading should be there$/) do
  on(ItemPage).sitelink_heading_element.when_visible
end

Then(/^Sitelink heading should not be there$/) do
  on(ItemPage).sitelink_heading_element.when_not_visible
end

Then(/^Sitelink remove button should be there$/) do
  on(ItemPage).remove_sitelink_link_element.when_visible
end

Then(/^Sitelink remove button should not be there$/) do
  on(ItemPage).remove_sitelink_link_element.when_not_visible
end

Then(/^Sitelink remove button should be disabled$/) do
  on(ItemPage).remove_sitelink_link_element.when_not_visible
  on(ItemPage).remove_sitelink_link_disabled_element.when_visible
end

Then(/^Sitelink edit button should be there$/) do
  on(ItemPage).edit_sitelink_link_element.when_visible
end

Then(/^Sitelink edit button should not be there$/) do
  on(ItemPage).edit_sitelink_link_element.when_not_visible
end

Then(/^Sitelink edit button should be disabled$/) do
  on(ItemPage).edit_sitelink_link_element.when_not_visible
  on(ItemPage).edit_sitelink_link_disabled_element.when_visible
end

Then(/^Sitelink save button should be there$/) do
  on(ItemPage).save_sitelink_link_element.when_visible
end

Then(/^Sitelink save button should not be there$/) do
  on(ItemPage).save_sitelink_link_element.when_not_visible
end

Then(/^Sitelink save button should be disabled$/) do
  on(ItemPage).save_sitelink_link_element.when_not_visible
  on(ItemPage).save_sitelink_link_disabled_element.when_visible
end

Then(/^Sitelink cancel button should be there$/) do
  on(ItemPage).cancel_sitelink_link_element.when_visible
end

Then(/^Sitelink cancel button should not be there$/) do
  on(ItemPage).cancel_sitelink_link_element.when_not_visible
end

Then(/^Sitelink counter should be there$/) do
  on(ItemPage).sitelink_counter_element.when_visible
end

Then(/^Sitelink counter should show (.+)$/) do |value|
  expect(on(ItemPage).number_of_sitelinks_from_counter).to be == value
end

Then(/^There should be (\d+) sitelinks in the list$/) do |num|
  expect(on(ItemPage).count_existing_sitelinks).to be == num.to_i
end

Then(/^Sitelink help field should be there$/) do
  on(ItemPage).sitelink_help_field_element.when_visible
end

Then(/^Sitelink siteid input field should be there$/) do
  on(ItemPage).sitelinks_form[1].site_id_input_field_element.when_present
end

Then(/^Sitelink siteid input field should not be there$/) do
  on(ItemPage).sitelinks_form[1].site_id_input_field_element.when_not_present
end

Then(/^Sitelink pagename input field should be there$/) do
  on(ItemPage).sitelinks_form[1].page_input_field_element.when_present
end

Then(/^Sitelink pagename input field should not be there$/) do
  on(ItemPage).sitelinks_form[1].page_input_field_element.when_not_present
end

Then(/^Sitelink pagename input field should be disabled$/) do
  on(ItemPage) do |page|
    page.sitelinks_form[1].page_input_field_element.when_not_present
    page.sitelinks_form[1].page_input_field_element_disabled.when_present
  end
end

Then(/^Sitelink siteid dropdown should be there$/) do
  on(ItemPage).site_id_dropdown_element.when_visible
end

Then(/^Sitelink siteid dropdown should not be there$/) do
  on(ItemPage).site_id_dropdown_element.when_not_visible
end

Then(/^Sitelink siteid first suggestion should be (.+)$/) do |value|
  expect(on(ItemPage).site_id_dropdown_first_element).to be == value
end

Then(/^Sitelink siteid first suggestion should include (.+)$/) do |value|
  expect(on(ItemPage).site_id_dropdown_first_element.include?(value)).to be true
end

Then(/^Sitelink pagename dropdown should be there$/) do
  on(ItemPage).page_name_dropdown_element.when_visible
end

Then(/^Sitelink pagename dropdown should not be there$/) do
  on(ItemPage).page_name_dropdown_element.when_not_visible
end

Then(/^Sitelink pagename first suggestion should be (.+)$/) do |value|
  expect(on(ItemPage).page_name_dropdown_first_element).to be == value
end

Then(/^Sitelink language code should be (.+)$/) do |value|
  expect(on(ItemPage).sitelink_siteid).to be == value
end

Then(/^Sitelink language code should include (.+)$/) do |value|
  expect(on(ItemPage).sitelink_siteid.include?(value)).to be true
end

Then(/^Sitelink link text should be (.+)$/) do |value|
  expect(on(ItemPage).sitelink_link_element.text).to be == value
end

Then(/^Sitelink link should lead to article (.+)$/) do |value|
  on(ItemPage) do |page|
    page.sitelink_link
    expect(page.article_title).to be == value
  end
end

Then(/^An error message should be displayed for sitelink group (.+)$/) do |group|
  on(ItemPage).sitelinks_sections[group].error_message_element.when_visible
end
