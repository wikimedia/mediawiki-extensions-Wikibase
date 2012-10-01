# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for CreateProperty special page

class CreatePropertyPage < CreateEntityPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:CreateProperty"

  select_list(:createPropertyDatatype, :id => 'wb-createproperty-datatype' )

  def create_new_property(label, description, datatype, switch_lang = true)
    if switch_lang
      self.uls_switch_language(LANGUAGE)
    end
    self.createEntityLabelField = label
    self.createEntityDescriptionField = description
    self.createPropertyDatatype = datatype
    createEntitySubmit
    wait_for_entity_to_load
    @@property_url = current_url
    query_string = "/" + PROPERTY_NAMESPACE + PROPERTY_ID_PREFIX
    @@property_id = @@property_url[@@property_url.index(query_string)+query_string.length..-1]
    return @@property_id
  end
end
