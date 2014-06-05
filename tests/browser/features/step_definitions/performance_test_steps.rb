# -*- encoding : utf-8 -*-
# Wikidata item tests
#
# License:: GNU GPL v2+
#
# steps for the performance test

items_under_test = {}

Given /^Entity (.+) defined in (.+) exists$/ do |pagename, data_file|
  wb_api = WikibaseAPI::Gateway.new(URL.repo_api)
  wb_api.login(ENV["WB_REPO_USERNAME"], ENV["WB_REPO_PASSWORD"])

  item_under_test = wb_api.wb_search_entities(pagename, "en", "item")['search'][0]
  if !item_under_test
    items = JSON.parse( IO.read( data_file ) )
    item_under_test = wb_api.create_entity_and_properties(items)
  end
  items_under_test[pagename] = item_under_test
end

When /^I load the huge item (.+)$/ do |pagename|
  on(ItemPage) do |page|
    page.navigate_to items_under_test[pagename]["url"]
    page.wait_for_entity_to_load
  end
end

Then /^Javascript UI should be initialized$/ do
  expect(on(ItemPage).edit_label_link_element.attribute("href")).to be == "javascript:void(0);"
end