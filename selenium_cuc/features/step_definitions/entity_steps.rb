# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# basic steps for entities

Given /^I am on an item page$/ do
  item_data = '{"labels":{"en":{"language":"en","value":"' + generate_random_string(8) + '"}},"descriptions":{"en":{"language":"en","value":"' + generate_random_string(20) + '"}}}'
  item = create_new_entity(item_data, 'item')
  @entity = item
  on(ItemPage).navigate_to_entity item["url"]
end

Given /^I am on an item page with empty label and description$/ do
  item_data = '{"labels":{"en":{"language":"en","value":"' + "" + '"}},"descriptions":{"en":{"language":"en","value":"' + "" + '"}}}'
  item = create_new_entity(item_data, 'item')
  @entity = item
  on(ItemPage).navigate_to_entity item["url"]
end

Then /^An error message should be displayed$/ do
  on(ItemPage).wbErrorDiv?.should be_true
end

When /^I reload the page$/ do
  @browser.refresh
end