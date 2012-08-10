# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for unblock user special page

class UnblockUserPage
  include PageObject

  page_url WIKI_REPO_URL + 'index.php?title=Special:Unblock'
  text_field(:unblock_username, :id => 'mw-input-wpTarget')
  button(:unblock, :class => 'mw-htmlform-submit')

  def unblock_user(username)
    self.unblock_username = username
    unblock
  end
end
