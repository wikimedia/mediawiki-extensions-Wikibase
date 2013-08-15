# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# common used methods

# creates a random string

def generate_random_string(length=8)
  chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
  string = ''
  length.times { string << chars[rand(chars.size)] }
  return string
end

def create_new_entity(data, type = 'item')
  uri = URI("http://localhost/mediawiki/api.php")

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
  response_json = ActiveSupport::JSON.decode(response.body)
  puts response_json["success"]
  #puts "Response #{response.code} #{response.message}: #{response.body}"
end