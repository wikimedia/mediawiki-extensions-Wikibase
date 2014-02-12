# -*- encoding : utf-8 -*-
# Wikidata item tests
#
# License:: GNU GPL v2+
#
# steps for the performance test

data_file_map = {
	'Italy' => 'data/italy.json'
}

Given(/^I am on page (.+)$/) do |pagename|
	wb_api = WikibaseAPI::Gateway.new(URL.repo_api)

	@item_under_test = wb_api.wb_search_entities(pagename, "en", "item")['search'][0]
	if !@item_under_test
		items = JSON.parse( IO.read( data_file_map[pagename] ) )
		@item_under_test = wb_api.create_entity_and_properties(items)
	end

	@startLoading = Time.now.to_i
	on(ItemPage).navigate_to @item_under_test["url"]
end

Then(/^get loading time of that page$/) do
	on(ItemPage).wait_for_entity_to_load
	endLoading = Time.now.to_i
	puts endLoading - @startLoading
end
