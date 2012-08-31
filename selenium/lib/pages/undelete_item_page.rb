# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for undelete special page

class UndeleteItemPage < ItemPage
  include PageObject

  button(:undelete, :id => 'mw-undelete-submit')

  def undelete_item
    navigate_to(WIKI_REPO_URL + "Special:Undelete/Data:Q" + @@item_id)
    undelete
  end
end
