# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for CreateItem special page

class CreateItemPage < ItemPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:CreateItem"

  text_field(:createItemLabelField, :id => "wb-createitem-label")
  text_field(:createItemDescriptionField, :id => "wb-createitem-description")
  button(:createItemSubmit, :id => "wb-createitem-submit")

end
