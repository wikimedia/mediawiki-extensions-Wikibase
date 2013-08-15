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
  chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
  string = ''
  length.times { string << chars[rand(chars.size)] }
  return string
end

# creates a new entity via the API
def create_new_entity(data, type = 'item')
  uri = URI(URL.repo_api)

  request = Net::HTTP::Post.new(uri.to_s)
  request.set_form_data(
    'action' => 'wbeditentity',
    'token' => '+\\',
    'new' => type,
    'data' => data,
    'format' => 'json'
  )

  response = Net::HTTP.start(uri.hostname, uri.port) do |http|
    http.request(request)
  end
  resp = ActiveSupport::JSON.decode(response.body)

  if resp["success"] != 1
    abort("Failed to create new entity: API error")
  end

  id = resp["entity"]["id"]
  url = URL.repo_url(id)
  return {"id" => id, "url" => url}
end