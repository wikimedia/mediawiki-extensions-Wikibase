# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for article move special page

class ClientMoveArticlePage < ItemPage
  include PageObject

  button(:move, :name => 'wpMove')
  text_field(:moveTo, :id => 'wpNewTitleMain')
  div(:updateInvitation)
  link(:updateLink, :xpath => "//div[@id='wbc-after-page-move']/a")

  def move_article(article_title, move_to)
    navigate_to(WIKI_CLIENT_URL + "Special:MovePage/" + article_title)
    self.moveTo_element.clear
    self.moveTo = move_to
    move
  end
end
