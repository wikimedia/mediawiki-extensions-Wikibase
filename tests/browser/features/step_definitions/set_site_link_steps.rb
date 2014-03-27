# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Katie Filbert < aude.wiki@gmail.com >
# License:: GNU GPL v2+
#
# tests for special set site link page

Given(/^I am on SetSiteLink special page$/) do
  visit_page(SetSiteLinkPage)
end

Then /^Entity id input element should be there/ do
  on(SetSiteLinkPage).set_site_link_entity_id_input_field?.should be_true
end

Then /^Site id input element should be there/ do
  on(SetSiteLinkPage).set_site_link_site_id_input_field?.should be_true
end

Then /^Page input element should be there/ do
  on(SetSiteLinkPage).set_site_link_page_input_field?.should be_true
end