# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for aliases page

module AliasPage
  include PageObject
  # aliases UI elements
  div(:aliases_div, class: 'wikibase-entitytermsview-heading-aliases')
  ul(:aliases_list, class: 'wikibase-entitytermsview-aliases')
  li(:aliases_list_item, class: 'wikibase-entitytermsview-aliases-alias')
  text_field(:aliases_input_first, xpath: "//div[contains(@class, 'wikibase-aliasesview')]//input")
  text_field(:aliases_input_empty, xpath: "//div[contains(@class, 'wikibase-aliasesview')]//li[contains(@class, 'tagadata-choice-empty')]//input")
  text_field(:aliases_input_modified, xpath: "//div[contains(@class, 'wikibase-aliasesview')]//li[contains(@class, 'tagadata-choice-modified')]//input")
  text_field(:aliases_input_equal, xpath: "//div[contains(@class, 'wikibase-aliasesview')]//li[contains(@class, 'tagadata-choice-equal')]//input")
  span(:aliases_help_field, css: 'div.wikibase-aliasesview span.wb-help-field-hint')

  # aliases methods
  def aliases_array
    aliases = []
    aliases_list_element.each do |alias_elem|
      aliases.push(alias_elem.text)
    end
    aliases
  end

  def count_existing_aliases
    count = 0
    if aliases_list? == false
      return 0
    end
    aliases_list_element.each { count += 1 }
    count
  end

  def populate_aliases(aliases)
    aliases.each do |ali|
      self.aliases_input_empty = ali
    end
  end
end
