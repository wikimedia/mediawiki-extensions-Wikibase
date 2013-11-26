# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# basic steps for entities

Given /^I am on an item page$/ do
  item_data = "{'labels':{'en':{'language':'en','value':'" + generate_random_string(8) + "'}},'descriptions':{'en':{'language':'en','value':'" + generate_random_string(20) + "'}}}"
  item = create_new_entity(item_data, "item")
  @item_under_test = item
  on(ItemPage).navigate_to_entity item["url"]
end

Given /^There are properties with the following handles and datatypes:$/ do |props|
  @properties = create_new_properties(props.raw)
end

Given /^There are items with the following handles:$/ do |handles|
  @items = create_new_items(handles.raw)
end

Given /^The copyright warning has been dismissed$/ do
  on(ItemPage).set_copyright_ack_cookie
end

Given /^Anonymous edit warnings are disabled$/ do
  on(ItemPage).set_noanonymouseditwarning_cookie
end

Given /^I am on an item page with empty label and description$/ do
  item_data = "{'labels':{'en':{'language':'en','value':'" + "" + "'}},'descriptions':{'en':{'language':'en','value':'" + "" + "'}}}"
  item = create_new_entity(item_data, "item")
  @item_under_test = item
  on(ItemPage).navigate_to_entity item["url"]
end

Given /^The following sitelinks do not exist:$/ do |sitelinks|
  sitelinks.raw.each do |sitelink|
    remove_sitelink(sitelink[0], sitelink[1]).should be_true
  end
end

Then /^An error message should be displayed$/ do
  on(ItemPage).wbErrorDiv?.should be_true
end

When /^I reload the page$/ do
  @browser.refresh
end
