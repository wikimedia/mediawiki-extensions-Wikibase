# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for delete entity action

class DeleteEntityPage < ItemPage
  include PageObject

  button(:delete, :id => 'wpConfirmB')

  def delete_entity(entity_url)
    navigate_to(entity_url + "?action=delete")
    delete
  end
end
