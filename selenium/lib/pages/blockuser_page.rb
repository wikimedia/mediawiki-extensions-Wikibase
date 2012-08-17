# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for block user special page

class BlockUserPage
  include PageObject

  page_url WIKI_REPO_URL + 'index.php?title=Special:Block'
  text_field(:block_username, :id => 'mw-bi-target')
  text_field(:expire_time, :id => 'mw-input-wpExpiry-other')
  button(:block, :class => 'mw-htmlform-submit')

  def block_user(username, expire)
    self.block_username = username
    self.expire_time = expire
    block
  end
end
