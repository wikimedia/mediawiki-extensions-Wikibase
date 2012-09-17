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
  div(:undeleteErrorDiv, :class => "error")
  link(:conflictingItemLink, :xpath => "//div[contains(@class, 'error')]/ul/li/a[2]")

  def undelete_item(item_id)
    navigate_to(WIKI_REPO_URL + "Special:Undelete/" + ITEM_NAMESPACE + ITEM_ID_PREFIX + item_id)
    undelete
  end
end
