# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for client login page

class ClientLoginPage < LoginPage
  include PageObject

  page_url WIKI_CLIENT_URL + 'Special:UserLogin'

end
