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
  div(:aliases_div, class: "wb-aliases")
  span(:aliases_title, class: "wb-aliases-label")
  ul(:aliases_list, class: "wb-aliases-container")
  a(:add_aliases, css: "div.wb-aliases a.wb-ui-propertyedittool-toolbarbutton-addbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:edit_aliases, css: "div.wb-aliases a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:save_aliases, css: "div.wb-aliases a.wikibase-toolbareditgroup-savebutton:not(.wikibase-toolbarbutton-disabled)")
  a(:save_aliases_disabled, css: "div.wb-aliases a.wikibase-toolbareditgroup-savebutton.wikibase-toolbarbutton-disabled")
  a(:cancel_aliases, css: "div.wb-aliases a.wikibase-toolbareditgroup-cancelbutton:not(.wikibase-toolbarbutton-disabled)")
  text_field(:aliases_input_first, xpath: "//div[contains(@class, 'wb-ui-aliasesedittool')]//li[contains(@class, 'wb-aliases-alias')]//input")
  a(:aliases_input_first_remove, css: "div.wb-aliases a.tagadata-close")
  text_field(:aliases_input_empty, xpath: "//div[contains(@class, 'wb-ui-aliasesedittool')]//li[contains(@class, 'tagadata-choice-empty')]//input")
  text_field(:aliases_input_modified, xpath: "//div[contains(@class, 'wb-ui-aliasesedittool')]//li[contains(@class, 'tagadata-choice-modified')]//input")
  text_field(:aliases_input_equal, xpath: "//div[contains(@class, 'wb-ui-aliasesedittool')]//li[contains(@class, 'tagadata-choice-equal')]//input")
  span(:aliases_help_field, css: "div.wb-aliases span.mw-help-field-hint")

  # aliases methods
  def get_aliases
    aliases = Array.new
    aliases_list_element.each do |aliasElem|
      aliases.push(aliasElem.text)
    end
    aliases
  end

  def count_existing_aliases
    count = 0
    if aliases_list? == false
      return 0
    end
    aliases_list_element.each do |aliasElem|
      count = count+1
    end
    count
  end

  def populate_aliases(aliases)
    aliases.each do |ali|
      self.aliases_input_empty= ali
    end
  end

end
