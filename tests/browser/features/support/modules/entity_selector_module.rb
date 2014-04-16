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
  ul(:entity_selector_list, css: ".ui-entityselector-list:not(.wikibase-entitysearch-list)")
  a(:first_entity_selector_link, css: ".ui-entityselector-list:not(.wikibase-entitysearch-list) li a")
  span(:first_entity_selector_label, xpath: "//ul[contains(@class, 'ui-entityselector-list')]/li/a/span/span[contains(@class, 'ui-entityselector-label')]")
  span(:first_entity_selector_description, xpath: "//ul[contains(@class, 'ui-entityselector-list')]/li/a/span/span[contains(@class, 'ui-entityselector-description')]")
  text_field(:entity_selector_input, xpath: "//div[contains(@class, 'wb-claimlistview')]//input[contains(@class, 'ui-entityselector-input')]", index: 0)
  text_field(:entity_selector_input2, xpath: "//div[contains(@class, 'wb-claimlistview')]//input[contains(@class, 'ui-entityselector-input')]", index: 1)

  #ul(:entity_selector_list, class: "ui-entityselector-list")
  #a(:first_entity_selector_link, xpath: "//ul[contains(@class, 'ui-entityselector-list')]/li/a")
  #span(:first_entity_selector_label, xpath: "//ul[contains(@class, 'ui-entityselector-list')]/li/a/span/span[contains(@class, 'ui-entityselector-label')]")
  #span(:first_entity_selector_description, xpath: "//ul[contains(@class, 'ui-entityselector-list')]/li/a/span/span[contains(@class, 'ui-entityselector-description')]")
  #text_field(:entity_selector_input2, xpath: "//div[contains(@class, 'wb-claimlistview')]//input[contains(@class, 'ui-entityselector-input')]", index: 1)
  #text_field(:entity_selector_search_input, id: "searchInput")
  #ul(:entity_selector_search, xpath: "//body/ul[contains(@class, 'ui-entityselector-list')]")

  def wait_for_entity_selector_list
    wait_until do
      entity_selector_list?
    end
  end

  def wait_for_suggestions_list
    wait_until do
      entity_selector_search?
    end
  end

  def select_entity(label)
    self.entity_selector_input = label
    ajax_wait
    self.wait_for_entity_selector_list
    first_entity_selector_link
    ajax_wait
  end

=begin
  def count_search_results
    entity_selector_search_element.items
  end

  def get_search_results
    results = []
    entity_selector_search_element.each do |li|
      results << li
    end
    results
  end
=end
end
