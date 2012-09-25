# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for CreateEntity special page base class

class CreateEntityPage < ItemPage
  include PageObject

  text_field(:createEntityLabelField, :id => "wb-createentity-label")
  text_field(:createEntityDescriptionField, :id => "wb-createentity-description")
  button(:createEntitySubmit, :id => "wb-createentiy-submit")

end
