require 'net/http'
require 'uri'
require 'json'

WIKI_URL = "http://localhost/mediawiki/"
WIKI_USELANG = "en"
WIKI_SKIN = "vector" # "vector" "monobook"
WIKI_API_URL = WIKI_URL + 'api.php'
WIKI_ARTICLE_TO_TEST = "Berlin"

class RubySelenium
  
  def self.get_url
    url = WIKI_URL + "index.php/" + WIKI_ARTICLE_TO_TEST + "?uselang=" + WIKI_USELANG + "&useskin=" + WIKI_SKIN
    return url
  end
  
end
