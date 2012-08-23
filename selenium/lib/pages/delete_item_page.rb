# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for delete item action

class DeleteItemPage < ItemPage
  include PageObject

  button(:delete, :id => 'wpConfirmB')

  def delete_item
    navigate_to(@@item_url + "?action=delete")
    delete
  end
end
