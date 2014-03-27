# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Katie Filbert < aude.wiki@gmail.com >
# License:: GNU GPL v2+
#
# tests for special set site link page

Given(/^I am on the Special:SetSiteLink special page$/) do
  visit_page(SpecialSetSitelinkPage)
end

Given /^I am on the Special:SetSiteLink special page for item (.+)$/ do |item_handle|
  on(SpecialSetSitelinkPage).navigate_to_page_with_item_id @items[item_handle]['id']
end

When /^I enter (.+) into the site id input field$/ do |site_id|
  on(SpecialSetSitelinkPage).site_id_input_field = site_id
end

When /^I enter (.+) into the page input field$/ do |page|
  on(SpecialSetSitelinkPage).page_input_field = page
end

When /^I press the set sitelink button$/ do
  on(SpecialSetSitelinkPage).set_sitelink_button
end

Then /^Site id input field should be there/ do
  on(SpecialSetSitelinkPage).site_id_input_field?.should be_true
end

Then /^Page input field should be there/ do
  on(SpecialSetSitelinkPage).page_input_field?.should be_true
end

Then /^Set sitelink button should be there$/ do
  on(SpecialSetSitelinkPage).set_sitelink_button?.should be_true
end
