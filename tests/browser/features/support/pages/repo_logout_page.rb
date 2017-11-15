# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Kreuz
# License:: GNU GPL v2+
#
# page object for repo logout page

class RepoLogoutPage
  include PageObject

  page_url URL.repo_url('Special:UserLogout')
end
