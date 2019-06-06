# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for delete item action

class DeleteItemPage
  include PageObject

  button(:delete, id: 'wpConfirmB')
  p(:return_to, id: 'mw-returnto')

  def delete_item(url)
    navigate_to(url + '&action=delete')
    delete
    return_to_element.when_visible
  end
end
