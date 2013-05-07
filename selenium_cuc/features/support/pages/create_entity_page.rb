# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for CreateEntity special page base class

class CreateEntityPage < EntityPage
  include PageObject

  text_field(:createEntityLabelField, :id => "wb-newentity-label")
  text_field(:createEntityDescriptionField, :id => "wb-newentity-description")
  button(:createEntitySubmit, :id => "wb-newentity-submit")
  div(:ipWarning, :xpath => "//div[@id='mw-content-text']/div[contains(@class, 'warning')]")

end
