# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for repo main page

class RepoMainPage < ItemPage
  include PageObject

  page_url WIKI_REPO_URL

end
