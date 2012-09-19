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

  text_field(:createItemLabelField, :id => "wb-createentity-label")
  text_field(:createItemDescriptionField, :id => "wb-createentity-description")
  button(:createItemSubmit, :id => "wb-createentiy-submit")

  def create_new_item(label, description)
    self.uls_switch_language(LANGUAGE)
    self.createItemLabelField = label
    self.createItemDescriptionField = description
    createItemSubmit
    wait_for_item_to_load
    @@item_url = current_url
    query_string = "/" + ITEM_NAMESPACE + ITEM_ID_PREFIX
    @@item_id = @@item_url[@@item_url.index(query_string)+query_string.length..-1]
    return @@item_id
  end
end
