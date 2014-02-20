# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# Parts reused and modified from http://rubygems.org/gems/mediawiki-gateway
#
# module for interacting with WikibaseAPI

require "rest_client"
require "uri"
require "active_support"

module WikibaseAPI

  class Gateway
    # Set up a WikibaseAPI::Gateway
    #
    # [url] Path to API of target Wikibase Installation
    def initialize(url)
      @wiki_url = url
      @headers = { "User-Agent" => "WikibaseAPI::Gateway", "Accept-Encoding" => "gzip" }
      @cookies = {}
    end

    # Login
    #
    # [username] Username
    # [password] Password
    #
    # Throws WikibaseAPI::Unauthorized if login fails
    def login(username, password, domain = "local")
      form_data = {"action" => "login", "lgname" => username, "lgpassword" => password, "lgdomain" => domain}
      make_api_request(form_data)
    end

    # creates a new entity via the API
    def wb_create_entity(data, type = "item")
      form_data = {"action" => "wbeditentity", "new" => type, "data" => data,
                   "summary" => "entity created by selenium test", "token" => get_token("edit")}
      resp = make_api_request(form_data)
      check_wb_api_success(resp)

      id = resp["entity"]["id"]
      url = URL.repo_url(ENV["ITEM_NAMESPACE"] + id + "?setlang=" + ENV["LANGUAGE_CODE"])
      entity_data = ActiveSupport::JSON.decode(data)

      {"id" => id, "url" => url, "label" => entity_data["labels"]["en"]["value"],
       "description" => entity_data["descriptions"]["en"]["value"]}
    end

    # creates new properties by calling wb_create_entity multiple times
    def wb_create_properties(props)
      properties = Hash.new

      props.each do |prop|
        handle = prop[0]
        type = prop[1]
        data = '{"labels":{"en":{"language":"en","value":"' + generate_random_string(8) +
            '"}},"descriptions":{"en":{"language":"en","value":"' + generate_random_string(20) +
            '"}},"datatype":"' + type + '"}'
        property = wb_create_entity(data, "property")
        properties[handle] = property
      end

      properties
    end

    # creates items by calling wb_create_entity multiple times
    def wb_create_items(handles)
      items = Hash.new

      handles.each do |handle|
        data = '{"labels":{"en":{"language":"en","value":"' + generate_random_string(8) +
            '"}},"descriptions":{"en":{"language":"en","value":"' + generate_random_string(20) + '"}}}'
        item = wb_create_entity(data, "item")
        items[handle] = item
      end

      items
    end

    # sets a sitelink
    def wb_set_sitelink(entity_identifier, linksite, linktitle)
      form_data = entity_identifier.merge({"action" => "wbsetsitelink", "linksite" => linksite, "linktitle" => linktitle,
                   "summary" => "Sitelink set by Selenium test API", "token" => get_token("edit")})

      make_api_request(form_data)
    end

    # removes a sitelink
    def wb_remove_sitelink(siteid, pagename)
      entity_identifier = {"site" => siteid, "title" => pagename}
      resp = wb_set_sitelink(entity_identifier, siteid, "")

      if resp["success"] != 1 && resp["error"]["code"] != "no-such-entity-link"
        msg = "Failed to remove sitelink " + siteid + ": API error"
        raise APIError.new("error", msg)
      end

      return true
    end

    def wb_search_entities(search, language, type)
      form_data = {"action" => "wbsearchentities", "search" => search, "language" => language, "type" => type}
      resp = make_api_request(form_data)
    end

    def create_entity_and_properties(serialization)
      serialization['properties'].each do |oldId, prop|
        if prop['description'] and prop['description']['en']['value']
          search = prop['description']['en']['value']
        else
          search = prop['labels']['en']['value']
        end
        resp = wb_search_entities(search, "en", "property")
        resp['search'].reject! do |foundProp|
          foundProp['label'] != prop['labels']['en']['value']
        end
        if resp['search'][0]
          id = resp['search'][0]['id']
        else
          savedProp = create_entity(prop, "property")
          id = savedProp['id']
        end

        serialization['entity']['claims'].each do |claim|
          if claim['mainsnak']['property'] == oldId
            claim['mainsnak']['property'] = id
          end
          if claim['qualifiers']
            claim['qualifiers'].each do |qualifier|
              if qualifier['property'] == oldId
                qualifier['property'] = id
              end
            end
          end
          if claim['qualifiers-order']
            claim['qualifiers-order'].map! do |pId|
              if pId == oldId then id else pId end
            end
          end
          if claim['references']
            claim['references'].each do |reference|
              reference['snaks'].each do |snak|
                if snak['property'] == oldId
                  snak['property'] = id
                end
              end
              reference['snaks-order'].map! do |pId|
                if pId == oldId then id else pId end
              end
            end
          end
        end
      end

      return create_entity(serialization['entity'], "item")
    end

    private

    def create_entity(entity, type)
      sitelinks = []
      if entity['sitelinks']
        sitelinks = entity['sitelinks']
        entity.delete('sitelinks')
      end

      storedEntity = wb_create_entity(JSON.generate(entity), type)
      sitelinks.each do |k, sitelink|
        wb_set_sitelink({'id' => storedEntity['id']}, sitelink['site'], sitelink['title'])
      end
      storedEntity
    end

    # Fetch token (type "delete", "edit", "email", "import", "move", "protect")
    def get_token(type)
      form_data = {"action" => "tokens", "type" => type}
      res = make_api_request(form_data)
      token = res["tokens"][type + "token"]
      raise Unauthorized.new "User is not permitted to perform this operation: #{type}" if token.nil?
      token
    end

    # Make generic request to API
    #
    # [form_data] hash or string of attributes to post
    #
    # Returns JSON document
    def make_api_request(form_data)
      form_data.merge!("format" => "json")
      http_send(@wiki_url, form_data, @headers.merge({:cookies => @cookies})) do |response, &block|
        # Check response for errors and return JSON
        raise WBException::Exception.new "Bad response: #{response}" unless response.code >= 200 and response.code < 300
        json_resp = get_response(response.dup)
        if form_data["action"] == "login"
          login_result = json_resp["login"]["result"]
          @cookies.merge!(response.cookies)
          case login_result
            when "Success" then # do nothing
            when "NeedToken" then make_api_request(form_data.merge("lgtoken" => json_resp["login"]["token"]))
            else raise Unauthorized.new "Login failed: " + login_result
          end
        end
        return json_resp
      end
    end

    # Execute the HTTP request using either GET or POST as appropriate
    def http_send(url, form_data, headers, &block)
      if form_data["action"] == "query"
        headers[:params] = form_data
        RestClient.get url, headers, &block
      else
        RestClient.post url, form_data, headers, &block
      end
    end

    # Get JSON response
    def get_response(response)
      ActiveSupport::JSON.decode(response)
    end

    def warning(msg)
        raise APIError.new("warning", msg)
    end

    def check_wb_api_success(response)
      if response["success"] != 1
        msg = "API error: " + response["error"]["info"]
        raise APIError.new("error", msg)
      end
    end

  end

  # General exception occurred within WikibaseAPI::Gateway, and parent class for WikibaseAPI::APIError, WikibaseAPI::Unauthorized.
  class WBException < Exception
  end

  # Wrapper for errors returned by MediaWiki API.  Possible codes are defined in http://www.mediawiki.org/wiki/API:Errors_and_warnings.
  # Warnings also throw errors with code "warning"
  class APIError < WikibaseAPI::WBException

    def initialize(code, info)
      @code = code
      @info = info
      @message = "API error: code '#{code}', info '#{info}'"
    end

    def to_s
      "#{self.class.to_s}: #{@message}"
    end
  end

  # User is not authorized to perform this operation.  Also thrown if login fails.
  class Unauthorized < WikibaseAPI::WBException
  end
end
