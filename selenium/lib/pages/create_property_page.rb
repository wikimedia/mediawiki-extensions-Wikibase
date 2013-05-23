# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for NewProperty special page

class NewPropertyPage < CreateEntityPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:NewProperty"

  select_list(:newPropertyDatatype, :id => 'wb-newproperty-datatype' )

  def create_new_property(label, description, datatype = "Item", switch_lang = true, dismiss_copyright = true)
    if switch_lang
      self.uls_switch_language(LANGUAGE_CODE, LANGUAGE_NAME)
    end
    if dismiss_copyright
      self.set_copyright_ack_cookie
    end
    self.createEntityLabelField = label
    self.createEntityDescriptionField = description
    self.newPropertyDatatype = datatype
    createEntitySubmit
    wait_for_entity_to_load
    @@property_url = current_url
    query_string = "/" + PROPERTY_NAMESPACE + PROPERTY_ID_PREFIX
    @@property_id = @@property_url[@@property_url.index(query_string)+query_string.length..-1]
    return @@property_id
  end
end
