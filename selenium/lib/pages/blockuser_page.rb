# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for block user special page

class BlockUserPage
  include PageObject

  page_url WIKI_REPO_URL + 'Special:Block'
  text_field(:blockUsername, :id => 'mw-bi-target')
  text_field(:expireTime, :id => 'mw-input-wpExpiry-other')
  button(:block, :class => 'mw-htmlform-submit')

  def block_user(username, expire)
    self.blockUsername = username
    self.expireTime = expire
    block
  end
end
