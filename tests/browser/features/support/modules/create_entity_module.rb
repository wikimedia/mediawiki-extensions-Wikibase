# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for CreateEntity special page base class

module CreateEntityPage
  include PageObject
  include EntityPage

  text_field(:create_entity_label_field, id: 'wb-newentity-label')
  text_field(:create_entity_description_field, id: 'wb-newentity-description')
  button(:create_entity_submit, id: 'wb-newentity-submit')
  div(:ip_warning, xpath: "//div[@id='mw-content-text']/div[contains(@class, 'warning')]")
end
