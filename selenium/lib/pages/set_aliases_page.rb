# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for SetAliases special page

class SetAliasesPage < SetEntityPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:SetAliases"

  button(:setAliasesSubmit, :id => "wb-setaliases-submit")

end
