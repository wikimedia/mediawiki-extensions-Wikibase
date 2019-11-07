# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for entity selector widget page

module EntitySelectorPage
  include PageObject
  # entity selector widget UI elements
  ul(:entity_selector_list, css: '.ui-entityselector-list:not(.wikibase-entitysearch-list)')
  a(:first_entity_selector_link, css: '.ui-entityselector-list:not(.wikibase-entitysearch-list) li a')
  a(:first_suggested_entity_link, xpath: '//ul[contains(@class, "ui-entityselector-list")][not(contains(@style,"display: none"))][not(contains(@class, "wikibase-entitysearch-list"))]//a')
  span(:first_entity_selector_label, xpath: "//ul[contains(@class, 'ui-entityselector-list')]/li/a/span/span[contains(@class, 'ui-entityselector-label')]")
  span(:first_entity_selector_description, xpath: "//ul[contains(@class, 'ui-entityselector-list')]/li/a/span/span[contains(@class, 'ui-entityselector-description')]")
  text_field(:claim_entity_selector_input, css: 'div.wikibase-statementview-mainsnak input.ui-entityselector-input')

  def snak_entity_selector_input(snak_index = 1)
    element('text_field', css: "div.wikibase-snaklistview:nth-child(#{snak_index}) input.ui-entityselector-input")
  end

  def wait_for_entity_selector_list
    wait_until do
      entity_selector_list?
    end
  end

  def select_claim_property(property_label)
    claim_entity_selector_input_element.clear
    self.claim_entity_selector_input = property_label
    ajax_wait
    wait_for_entity_selector_list
    first_suggested_entity_link_element.when_visible.click
  end

  def select_snak_property(property_label, snak_index = 1)
    snak_entity_selector_input(snak_index).when_visible.clear
    snak_entity_selector_input(snak_index).send_keys property_label
    ajax_wait
    first_suggested_entity_link_element.when_visible.click
  end
end
