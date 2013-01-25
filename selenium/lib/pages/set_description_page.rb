# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for SetDescription special page

class SetDescriptionPage < SetEntityPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:SetDescription"

  button(:setDescriptionSubmit, :id => "wb-setdescription-submit")

end
