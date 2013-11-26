# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# common used methods

include URL

# creates a random string
def generate_random_string(length=8)
  chars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ"
  string = ""
  length.times { string << chars[rand(chars.size)] }
  return string
end

def create_new_properties(props)
  properties = Hash.new

  props.each do |prop|
    handle = prop[0]
    type = prop[1]
    data = "{'labels':{'en':{'language':'en','value':'" + generate_random_string(8) +
           "'}},'descriptions':{'en':{'language':'en','value':'" + generate_random_string(20) +
           "'}},'datatype':'" + type + "'}"
    property = create_new_entity(data, "property")
    properties[handle] = property
  end

  properties
end

def create_new_items(handles)
  items = Hash.new

  handles.each do |handle|
    data = "{'labels':{'en':{'language':'en','value':'" + generate_random_string(8) +
           "'}},'descriptions':{'en':{'language':'en','value':'" + generate_random_string(20) + "'}}}"
    item = create_new_entity(data, "item")
    items[handle] = item
  end

  items
end

# creates a new entity via the API
def create_new_entity(data, type = "item")
  uri = URI(URL.repo_api)

  request = Net::HTTP::Post.new(uri.to_s)
  request.set_form_data(
    "action" => "wbeditentity",
    "token" => "+\',
    "new" => type,
    "data" => data,
    "format" => "json",
    "summary" => "entity created by selenium test"
  )

  response = Net::HTTP.start(uri.hostname, uri.port) do |http|
    http.request(request)
  end
  resp = ActiveSupport::JSON.decode(response.body)

  if resp["success"] != 1
    abort("Failed to create new entity: API error: " + resp["error"]["info"])
  end

  id = resp["entity"]["id"]
  url = URL.repo_url(ITEM_NAMESPACE + id + "?setlang=" + LANGUAGE_CODE)
  entity_data = ActiveSupport::JSON.decode(data)
  entity = {"id" => id, "url" => url, "label" => entity_data["labels"]["en"]["value"], "description" => entity_data["descriptions"]["en"]["value"]}
  return entity
end

# removes a sitelink
def remove_sitelink(siteid, pagename)
  uri = URI(URL.repo_api)

  request = Net::HTTP::Post.new(uri.to_s)
  request.set_form_data(
      "action" => "wbsetsitelink",
      "token" => "+\',
      "site" => siteid,
      "title" => pagename,
      "linksite" => siteid,
      "format" => "json",
      "summary" => "sitelink removed by selenium test"
  )

  response = Net::HTTP.start(uri.hostname, uri.port) do |http|
    http.request(request)
  end
  resp = ActiveSupport::JSON.decode(response.body)

  if resp["success"] != 1 && resp["error"]["code"] != "no-such-entity-link"
    abort("Failed to remove sitelink " + siteid + ": API error")
  end

  return true
end
