# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for SetSitelink special page

class SetSitelinkPage < SetEntityPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:SetSiteLink"

  text_field(:sitelinkSiteField, :id => "wb-setsitelink-site")
  text_field(:sitelinkPageField, :id => "wb-setsitelink-page")
  button(:setSitelinkSubmit, :id => "wb-setsitelink-submit")

end
