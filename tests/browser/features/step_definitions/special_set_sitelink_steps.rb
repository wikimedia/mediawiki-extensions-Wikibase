# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Katie Filbert < aude.wiki@gmail.com >
# License:: GNU GPL v2+
#
# tests for special set site link page

Given(/^I am on the SetSiteLink special page$/) do
  visit_page(SpecialSetSitelinkPage)
end

Given /^I am on the SetSiteLink special page for item (.+)$/ do |item_handle|
  on(SpecialSetSitelinkPage).navigate_to_page_with_item_id @items[item_handle]['id']
end

Then /^Site id input field should be there/ do
  on(SpecialSetSitelinkPage).set_sitelink_site_id_input_field?.should be_true
end

Then /^Page input field should be there/ do
  on(SpecialSetSitelinkPage).set_sitelink_page_input_field?.should be_true
end
