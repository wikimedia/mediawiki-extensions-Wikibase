# -*- encoding : utf-8 -*-
# Wikidata item tests
#
# License:: GNU GPL v2+
#
# steps for the performance test

def createBigEntity(wb_api, entity, type)
	sitelinks = []
	if entity['sitelinks']
		sitelinks = entity['sitelinks']
		entity.delete('sitelinks')
	end
	storedEntity = wb_api.wb_create_entity(JSON.generate(entity), type)
	sitelinks.each do |k, sitelink|
		wb_api.wb_set_sitelink({'id' => storedEntity['id']}, sitelink['title'], sitelink['site'])
	end
	storedEntity
end

Given(/^I am on Italy's page$/) do
	wb_api = WikibaseAPI::Gateway.new(URL.repo_api)

	@item_under_test = wb_api.wb_search_entities("Italy", "en", "item")['search'][0]
	if !@item_under_test
		items = JSON.parse( IO.read( 'data/italy.json' ) )

		items['entity']['claims'].each do |claim|
			claim['mainsnak']['property'] = 'old' + claim['mainsnak']['property']
		end

		items['properties'].each do |oldId, prop|
			if prop['description'] and prop['description']['en']['value']
				search = prop['description']['en']['value']
			else
				search = prop['labels']['en']['value']
			end
			resp = wb_api.wb_search_entities(search, "en", "property")
			resp['search'].reject! do |foundProp|
				foundProp['label'] != prop['labels']['en']['value']
			end
			if resp['search'][0]
				id = resp['search'][0]['id']
			else
				savedProp = createBigEntity(wb_api, prop, "property")
				id = savedProp['id']
			end
			items['entity']['claims'].each do |claim|
				if claim['mainsnak']['property'] == 'old' + oldId
					claim['mainsnak']['property'] = id
				end
			end
		end

		@item_under_test = createBigEntity(wb_api, items['entity'], "item")
	end

	client = Selenium::WebDriver::Remote::Http::Default.new
	client.timeout = 180 # seconds – default is 60

  profile = Selenium::WebDriver::Firefox::Profile.new
  profile["dom.max_script_run_time"] = 180
  #@browser = Watir::Browser.new :firefox, profile: profile, :http_client => client

  @browser = Watir::Browser.new :chrome, :http_client => client

  on(ItemPage).navigate_to @item_under_test["url"]
end

Then(/^check if this page is fast$/) do
	startLoading = Time.now
  on(ItemPage).wait_for_entity_to_load
	endLoading = Time.now
	puts ( endLoading.to_i - startLoading.to_i ).to_s
end
