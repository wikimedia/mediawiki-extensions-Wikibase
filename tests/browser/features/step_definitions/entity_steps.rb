# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# basic steps for entities

Given(/^I am logged in to the repo$/) do
  as_user(:b) do
    visit(RepoLoginPage).login_with(user(:b), password(:b))
  end
end

Given(/^I am not logged in to the repo$/) do
  visit(RepoLogoutPage)
end

Given(/^I am on an item page$/) do
  step 'I have an item to test'
  step 'I am on the page of the item to test'
end

Given(/^I have an item to test$/) do
  step 'I have an item with label "' + generate_random_string(8) + '" and description "' + generate_random_string(20) + '"'
end

Given(/^I have an item with empty label and description$/) do
  step 'I have an item with label "" and description ""'
end

Given(/^I have an item with label "([^"]*)"$/) do |label|
  step 'I have an item with label "' + label + '" and description "' + generate_random_string(20) + '"'
end

Given(/^I have (\d+) items beginning with "([^"]*)"$/) do |num, pre|
  (1..num.to_i).each do
    step 'I have an item with label "' + pre + generate_random_string(5) + '" and description "' + generate_random_string(20) + '"'
  end
end

Given(/^I have an item with label "(.*)" and description "(.*)"$/) do |label, description|
  item_data = '{"labels":{"en":{"language":"en","value":"' + label + '"}},"descriptions":{"en":{"language":"en","value":"' + description + '"}}}'
  @item_under_test = on(ItemPage).create_item(item_data)
end

Given(/^I am on the page of the item to test$/) do
  on(ItemPage).navigate_to_entity @item_under_test['url']
end

Given(/^I am on the page of item (.*)$/) do |item_handle|
  on(ItemPage).navigate_to_entity @items[item_handle]['url']
end

Given(/^I navigate to item (.*) with resource loader debug mode (.*)$/) do |item_id, debug_mode|
  entity_url = URL.repo_url(ENV['ITEM_NAMESPACE'] + item_id) + '&debug=' + debug_mode
  visit(ItemPage).navigate_to_entity entity_url
  @item_under_test = on(ItemPage).create_item_data_from_page
end

Given(/^I navigate to property (.*) with resource loader debug mode (.*)$/) do |property_id, debug_mode|
  entity_url = URL.repo_url(ENV['PROPERTY_NAMESPACE'] + property_id) + '&debug=' + debug_mode
  visit(ItemPage).navigate_to_entity entity_url
  @item_under_test = on(ItemPage).create_item_data_from_page
end

Given(/^I navigate to property id (.*)$/) do |property_id|
  entity_url = URL.repo_url(ENV['PROPERTY_NAMESPACE'] + property_id)
  visit(ItemPage).navigate_to_entity entity_url
  @item_under_test = on(ItemPage).create_item_data_from_page
end

Given(/^I navigate to property handle (.*)$/) do |handle|
  step 'I navigate to property id ' + @properties[handle]['id']
end

Given(/^I have the following properties with datatype:$/) do |props|
  property_data = on(PropertyPage).create_property_data(props)
  wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api

  as_user(:b) do
    wb_api.log_in(user(:b), password(:b))
  end

  @properties = on(PropertyPage).create_properties(property_data, wb_api)
end

Given(/^I have the following items:$/) do |handles|
  @items = visit(ItemPage).create_items(handles)
end

Given(/^I have the following empty items:$/) do |handles|
  @items = visit(ItemPage).create_items(handles, true)
end

Given(/^The copyright warning has been dismissed$/) do
  on(ItemPage).set_copyright_ack_cookie
end

Given(/^Anonymous edit warnings are disabled$/) do
  on(ItemPage).set_noanonymouseditwarning_cookie
end

Given(/^I am on an item page with empty label and description$/) do
  step 'I have an item with empty label and description'
  step 'I am on the page of the item to test'
end

Given(/^The following sitelinks do not exist:$/) do |sitelinks|
  wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api

  as_user(:b) do
    wb_api.log_in(user(:b), password(:b))
  end

  sitelinks.raw.each do |sitelink|
    if wb_api.sitelink_exists?(sitelink[0], sitelink[1])
      wb_api.remove_sitelink({ site_id: sitelink[0], title: sitelink[1] }, sitelink[0])
    end
  end
end

Then(/^An error message should be displayed$/) do
  expect(on(ItemPage).wb_error_div?).to be true
end

When(/^I reload the page$/) do
  browser.refresh
  on(ItemPage).wait_for_entity_to_load
end
