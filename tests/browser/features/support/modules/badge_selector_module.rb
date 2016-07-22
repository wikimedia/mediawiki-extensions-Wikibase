# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Christoph Fischer (christoph.fischer@wikimedia.de)
# License:: GNU GPL v2+
#
# module for snaktype selector page object

module BadgeSelectorPage
  include PageObject

  ul(:badge_selector_menu, css: 'ul.wikibase-badgeselector-menu')

  indexed_property(:badge_selector_list, [
    [:span, :selector_link, { css: 'ul.wikibase-badgeselector-menu li.ui-menu-item:nth-child(%s) span.wb-badge' }],
    [:a, :selector_id_link, { css: 'ul.wikibase-badgeselector-menu li.wikibase-badgeselector-menuitem-%s a' }]
  ])

  indexed_property(:badge_list, [
    [:span, :badge, { xpath: "//span[contains(@class,'wikibase-badgeselector')][1]/descendant::span[contains(@data-wb-badge,'%s')]" }]
  ])

  def available_badges
    mw_api = MediawikiApi::Client.new URL.repo_api
    response = mw_api.action(:wbavailablebadges, token_type: false)
    response['badges']
  end
end
