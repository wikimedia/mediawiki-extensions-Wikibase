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
  on(ItemPage) do |page|
    page.navigate_to_entity item["url"]
    page.set_copyright_ack_cookie
    page.set_noanonymouseditwarning_cookie
  end

end

Given /^I am on an item page with empty label and description$/ do
  item_data = '{"labels":{"en":{"language":"en","value":"' + "" + '"}},"descriptions":{"en":{"language":"en","value":"' + "" + '"}}}'
  item = create_new_entity(item_data, 'item')
  @entity = item
  on(ItemPage).navigate_to_entity item["url"]
end

Given /^the sitelink (.+)\/(.+) does not exist$/ do |siteid, pagename|
  remove_sitelinks(siteid, pagename).should be_true
end

Given /^the sitelinks (.+) \/ (.+) do not exist$/ do |siteids, pagenames|
  eval("[#{siteids}]").zip(eval("[#{pagenames}]")).each do |siteid, pagename|
    remove_sitelinks(siteid, pagename).should be_true
  end
end

Then /^An error message should be displayed$/ do
  on(ItemPage).wbErrorDiv?.should be_true
end

When /^I reload the page$/ do
  @browser.refresh
end