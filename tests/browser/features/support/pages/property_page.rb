# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for property page

class PropertyPage
  include PageObject
  include EntityPage

  span(:property_datatype_heading, id: 'datatype')
  div(:property_datatype, css: 'div.wikibase-propertyview-datatype-value')

  # ***** METHODS *****
  def create_property_data(props)
    property_data = {}
    props.raw.each do |prop|
      handle = prop[0]
      type = prop[1]
      label = generate_random_string(8)
      description = generate_random_string(20)
      data = '{"labels":{"en":{"language":"en","value":"' + label +
             '"}},"descriptions":{"en":{"language":"en","value":"' + description +
             '"}},"datatype":"' + type + '"}'
      property_data[handle] = data
    end

    property_data
  end

  def create_property(data, wb_api)
    resp = wb_api.create_property(data)
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

    url = URL.repo_url(ENV['PROPERTY_NAMESPACE'] + id)

    { 'id' => id, 'url' => url, 'label' => label_en, 'description' => description_en }
  end

  def create_properties(property_data, wb_api)
    properties = {}
    property_data.each do |handle, data|
      property = create_property(data, wb_api)
      properties[handle] = property
    end

    page_titles = properties.map { |_, data| ENV['PROPERTY_NAMESPACE'] + data['id'] }
    wait_for_search_index_update(page_titles)

    properties
  end
end
