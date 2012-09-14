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

  def create_new_item(label, description)
    self.createItemLabelField = label
    self.createItemDescriptionField = description
    createItemSubmit
    wait_for_item_to_load
    @@item_url = current_url
    #@@item_id = @@item_url[@@item_url.index('/Q')+2..-1]
    query_string = "/" + ITEM_NAMESPACE + "Q"
    @@item_id = @@item_url[@@item_url.index(query_string)+query_string.length..-1]
    return @@item_id
  end
end
