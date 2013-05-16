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
  unordered_list(:entitySelectorList, :class => "ui-entityselector-list")
  link(:firstEntitySelectorLink, :xpath => "//ul[contains(@class, 'ui-entityselector-list')]/li/a")
  span(:firstEntitySelectorLabel, :xpath => "//ul[contains(@class, 'ui-entityselector-list')]/li/a/span/span[contains(@class, 'ui-entityselector-label')]")
  span(:firstEntitySelectorDescription, :xpath => "//ul[contains(@class, 'ui-entityselector-list')]/li/a/span/span[contains(@class, 'ui-entityselector-description')]")
  text_field(:entitySelectorInput, :xpath => "//div[contains(@class, 'wb-claimlist')]//input[contains(@class, 'ui-entityselector-input')]", :index => 0)
  text_field(:entitySelectorInput2, :xpath => "//div[contains(@class, 'wb-claimlist')]//input[contains(@class, 'ui-entityselector-input')]", :index => 1)
  text_field(:entitySelectorSearchInput, :id => "searchInput")
  unordered_list(:entitySelectorSearch, :xpath => "//body/ul[contains(@class, 'ui-entityselector-list')]")

  def wait_for_entity_selector_list
    wait_until do
      entitySelectorList?
    end
  end

  def wait_for_suggestions_list
    wait_until do
      entitySelectorSearch?
    end
  end

  def select_entity(label)
    self.entitySelectorInput = label
    ajax_wait
    self.wait_for_entity_selector_list
    firstEntitySelectorLink
    ajax_wait
  end

  def count_search_results
    entitySelectorSearch_element.items
  end

  def get_search_results
    results = []
    entitySelectorSearch_element.each do |li|
      results << li
    end
    results
  end

end
