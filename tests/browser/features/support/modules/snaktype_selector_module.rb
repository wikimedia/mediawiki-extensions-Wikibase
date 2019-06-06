# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for snaktype selector page object

module SnaktypeSelectorPage
  include PageObject

  ul(:snaktype_selector_menu, css: 'ul.wikibase-snaktypeselector-menu')

  def snaktype_selector(group_index, claim_index)
    element('span', css: "div.wikibase-statementgrouplistview div.wikibase-statementgroupview:nth-child(#{group_index}) div.wikibase-statementview:nth-child(#{claim_index}) .wikibase-snakview-typeselector > span > span")
  end

  indexed_property(:snaktype_list, [
    [:a, :item, { css: 'ul.wikibase-snaktypeselector-menu > li.wikibase-snaktypeselector-menuitem-%s a' }]
  ])

  def select_snaktype(group_index, claim_index, snaktype)
    snaktype_selector(group_index, claim_index).when_visible.click
    snaktype_selector_menu_element.when_visible
    # the following is to work around an issue where webdriver does behaves
    # differently when doing element.click and element.fire_event('onClick')
    # in (at least) firefox.
    # see also https://groups.google.com/forum/#!topic/watir-general/iYH-_OX1PPg
    snaktype_list[snaktype].item_element.when_visible.fire_event('onClick')
    snaktype_list[snaktype].item_element.when_visible.click
    ajax_wait
  end
end
