# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for item page

class ItemPage
  include PageObject
  include EntityPage

  def create_item_data(handles, empty = false)
    item_data = {}
    handles.raw.each do |handle|
      handle = handle[0]
      label = empty ? '' : generate_random_string(8)
      description = empty ? '' : generate_random_string(20)
      data = '{"labels":{"en":{"language":"en","value":"' + label +
             '"}},"descriptions":{"en":{"language":"en","value":"' + description + '"}}}'
      item_data[handle] = data
    end

    item_data
  end

  def create_item(data)
    wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api
    resp = wb_api.create_item(data)

    id = resp['entity']['id']

    if resp['entity']['labels'].length > 0 && resp['entity']['labels']['en']
      label_en = resp['entity']['labels']['en']['value']
    else
      label_en = ''
    end

    if resp['entity']['descriptions'].length > 0 && resp['entity']['descriptions']['en']
      description_en = resp['entity']['descriptions']['en']['value']
    else
      description_en = ''
    end

    url = URL.repo_url(ENV['ITEM_NAMESPACE'] + id)

    { 'id' => id, 'url' => url, 'label' => label_en, 'description' => description_en }
  end

  def create_items(handles, empty = false)
    item_data = create_item_data(handles, empty)
    items = {}
    item_data.each do |handle, data|
      item = create_item(data)
      items[handle] = item
    end

    page_titles = items.map { |_, data| ENV['ITEM_NAMESPACE'] + data['id'] }
    wait_for_search_index_update(page_titles)

    items
  end
end
