# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for CreateItem special page

class CreateItemPage < CreateEntityPage
  include PageObject
  include URL

  page_url URL.repo_url("Special:NewItem")

  def create_new_item(label, description, switch_lang = true)
    if switch_lang
      self.uls_switch_language(LANGUAGE_CODE, LANGUAGE_NAME)
    end
    self.createEntityLabelField = label
    self.createEntityDescriptionField = description
    createEntitySubmit
    wait_for_entity_to_load
    @@item_url = current_url
    query_string = "/" + ITEM_NAMESPACE + ITEM_ID_PREFIX
    @@item_id = @@item_url[@@item_url.index(query_string)+query_string.length..-1]
    return @@item_id
  end
end
