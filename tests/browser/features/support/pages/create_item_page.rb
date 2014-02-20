# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for CreateItem special page

class CreateItemPage
  include PageObject
  include CreateEntityPage
  include URL

  page_url URL.repo_url("Special:NewItem")

  def create_new_item(label, description, switch_lang = true)
    if switch_lang
      self.uls_switch_language(ENV["LANGUAGE_CODE"], ENV["LANGUAGE_NAME"])
    end
    self.create_entity_label_field = label
    self.create_entity_description_field = description
    create_entity_submit
    wait_for_entity_to_load
    @@item_url = current_url
    query_string = "/" + ENV["ITEM_NAMESPACE"] + ENV["ITEM_ID_PREFIX"]
    @@item_id = @@item_url[@@item_url.index(query_string)+query_string.length..-1]
    return @@item_id
  end
end
