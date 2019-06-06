# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for rank selector page object

module RankSelectorPage
  include PageObject

  ul(:rank_selector_menu, css: 'ul.wikibase-rankselector-menu')

  def rank_indicator(group_index, claim_index, rank)
    element('span', css: "div.wikibase-statementgrouplistview div.wikibase-statementgroupview:nth-child(#{group_index}) div.wikibase-statementview:nth-child(#{claim_index}) .wikibase-rankselector.ui-state-disabled > span.wikibase-rankselector-#{rank}")
  end

  def rank_selector_disabled(group_index, claim_index)
    element('span', css: "div.wikibase-statementgrouplistview div.wikibase-statementgroupview:nth-child(#{group_index}) div.wikibase-statementview:nth-child(#{claim_index}) .wikibase-rankselector.ui-state-disabled > span")
  end

  def rank_selector(group_index, claim_index)
    element('span', css: "div.wikibase-statementgrouplistview div.wikibase-statementgroupview:nth-child(#{group_index}) div.wikibase-statementview:nth-child(#{claim_index}) .wikibase-rankselector:not(.ui-state-disabled) > span")
  end

  indexed_property(:rank_list, [
    [:a, :item, { css: 'ul.wikibase-rankselector-menu > li.wikibase-rankselector-menuitem-%s a' }]
  ])

  def select_rank(group_index, claim_index, rank)
    rank_selector(group_index, claim_index).when_visible.click
    rank_selector_menu_element.when_visible
    # the following is to work around an issue where webdriver does behaves
    # differently when doing element.click and element.fire_event('onClick')
    # in (at least) firefox.
    # see also https://groups.google.com/forum/#!topic/watir-general/iYH-_OX1PPg
    rank_list[rank].item_element.when_visible.fire_event('onClick')
    rank_list[rank].item_element.when_visible.click
    ajax_wait
  end
end
