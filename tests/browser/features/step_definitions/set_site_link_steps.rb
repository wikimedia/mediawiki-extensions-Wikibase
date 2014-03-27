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

Then /^Item id input element should be there/ do
  on(SetSiteLinkPage).set_site_link_item_id_input_field?.should be_true
end

Then /^Site id input element should be there/ do
  on(SetSiteLinkPage).set_site_link_site_id_input_field?.should be_true
end

Then /^Page input element should be there/ do
  on(SetSiteLinkPage).set_site_link_page_input_field?.should be_true
end

Given /^I am on SetSiteLink special page for item$/ do
  item_data = '{"labels":{"en":{"language":"en","value":"' + generate_random_string(8) + '"}},"descriptions":{"en":{"language":"en","value":"' + generate_random_string(20) + '"}}}'
  wb_api = WikibaseAPI::Gateway.new(URL.repo_api)
  @item_under_test = wb_api.wb_create_entity(item_data, "item")

  @params = {:id => @item_under_test['id']}
  on(SetSiteLinkPage).navigate_to_page_with_item_id @item_under_test['id']
end

Then /^Item id input element should contain item id/ do
  on(SetSiteLinkPage).set_site_link_item_id_input_field = @item_under_test['id']
end