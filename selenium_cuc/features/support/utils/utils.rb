# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# common used methods

include URL
include WikibaseAPI

@edit_token = "+\\"

# creates a random string
def generate_random_string(length=8)
  chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
  string = ''
  length.times { string << chars[rand(chars.size)] }
  return string
end

# removes a sitelink
def remove_sitelink(siteid, pagename)
  uri = URI(URL.repo_api)

  request = Net::HTTP::Post.new(uri.to_s)
  request.set_form_data(
      'action' => 'wbsetsitelink',
      'token' => @edit_token,
      'site' => siteid,
      'title' => pagename,
      'linksite' => siteid,
      'format' => 'json',
      'summary' => 'sitelink removed by selenium test'
  )

  response = Net::HTTP.start(uri.hostname, uri.port) do |http|
    http.request(request)
  end
  resp = ActiveSupport::JSON.decode(response.body)

  if resp["success"] != 1 && resp["error"]["code"] != 'no-such-entity-link'
    abort('Failed to remove sitelink ' + siteid + ': API error')
  end

  return true
end
