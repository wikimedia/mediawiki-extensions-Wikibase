# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# basic steps for entities

Given /^I am on an item page$/ do
  item_data = '{"labels":{"en":{"language":"en","value":"' + generate_random_string(8) + '"}},
                "descriptions":{"en":{"language":"en","value":"' + generate_random_string(20) + '"}}}'
  wb_api = WikibaseAPI::Gateway.new(URL.repo_api)
  @item_under_test = wb_api.wb_create_entity(item_data, "item")
  on(ItemPage).navigate_to_entity @item_under_test["url"]
end

Given /^There are properties with the following handles and datatypes:$/ do |props|
  wb_api = WikibaseAPI::Gateway.new(URL.repo_api)
  wb_api.login(ENV["WB_REPO_USERNAME"], ENV["WB_REPO_PASSWORD"])
  @properties = wb_api.wb_create_properties(props.raw)
end

Given /^There are items with the following handles:$/ do |handles|
  wb_api = WikibaseAPI::Gateway.new(URL.repo_api)
  @items = wb_api.wb_create_items(handles.raw)
end

Given /^There are statements with the following properties and values:$/ do |stmts|
  wb_api = WikibaseAPI::Gateway.new(URL.repo_api)
  wb_api.login(ENV["WB_REPO_USERNAME"], ENV["WB_REPO_PASSWORD"])
  item_id = @item_under_test["id"]

  stmts.raw.each do |statement|
    if statement[1] == ""
      statement[1] = generate_random_string(20)
    end
    claim_data = '{"type":"statement","mainsnak":{"snaktype":"value","property":"' +
        @properties[statement[0]]["id"] + '","datavalue":{"type":"string","value":"' + statement[1] + '"}},"id":"' +
        item_id + '$' + SecureRandom.uuid + '","qualifiers":{},"qualifiers-order":[],"rank":"normal"}'
    wb_api.wb_set_claim(item_id, claim_data)
  end

end

Given /^The copyright warning has been dismissed$/ do
  on(ItemPage).set_copyright_ack_cookie
end

Given /^Anonymous edit warnings are disabled$/ do
  on(ItemPage).set_noanonymouseditwarning_cookie
end

Given /^I am on an item page with empty label and description$/ do
  item_data = '{"labels":{"en":{"language":"en","value":"' + '' + '"}},
                "descriptions":{"en":{"language":"en","value":"' + '' + '"}}}'
  wb_api = WikibaseAPI::Gateway.new(URL.repo_api)
  @item_under_test = wb_api.wb_create_entity(item_data, "item")
  on(ItemPage).navigate_to_entity @item_under_test["url"]
end

Given /^The following sitelinks do not exist:$/ do |sitelinks|
  wb_api = WikibaseAPI::Gateway.new(URL.repo_api)
  sitelinks.raw.each do |sitelink|
    wb_api.wb_remove_sitelink(sitelink[0], sitelink[1]).should be_true
  end
end

Then /^An error message should be displayed$/ do
  on(ItemPage).wbErrorDiv?.should be_true
end

When /^I reload the page$/ do
  @browser.refresh
  on(ItemPage).wait_for_entity_to_load
end
