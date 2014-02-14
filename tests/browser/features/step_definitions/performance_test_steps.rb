# -*- encoding : utf-8 -*-
# Wikidata item tests
#
# License:: GNU GPL v2+
#
# steps for the performance test

data_file_map = {
  'Italy' => 'data/italy.json'
}

item_under_test = nil

Given(/^Entity (.+) exists$/) do |pagename|
  wb_api = WikibaseAPI::Gateway.new(URL.repo_api)
  wb_api.login(ENV["WB_REPO_USERNAME"], ENV["WB_REPO_PASSWORD"])

  item_under_test = wb_api.wb_search_entities(pagename, "en", "item")['search'][0]
  if !item_under_test
    items = JSON.parse( IO.read( data_file_map[pagename] ) )
    item_under_test = wb_api.create_entity_and_properties(items)
  end
end

Then(/^get loading time of huge item page$/) do
  on(ItemPage).navigate_to item_under_test["url"]
  on(ItemPage).wait_for_entity_to_load
end
