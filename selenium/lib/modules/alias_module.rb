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
  div(:aliases_div, :class => "wb-aliases")
  span(:aliases_title, :class => "wb-aliases-label")
  unordered_list(:aliases_list, :class => "wb-aliases-container")
  link(:add_aliases,				:css => "div.wb-aliases .wikibase-toolbar > .wikibase-toolbar > a.wikibase-toolbarbutton:not(.wikibase-toolbarbutton-disabled):nth-child(1)")
  link(:add_aliases_disabled,		:css => "div.wb-aliases .wikibase-toolbar > .wikibase-toolbar > a.wikibase-toolbarbutton-disabled:nth-child(1)")
  link(:edit_aliases,			:css => "div.wb-aliases > div > span.wb-ui-propertyedittool-editablevalue > span > span > span > span > a.wikibase-toolbarbutton:not(.wikibase-toolbarbutton-disabled):nth-child(1)")
  link(:save_aliases,			:css => "div.wb-aliases > div > span.wb-ui-propertyedittool-editablevalue > span > span > span > span > a.wikibase-toolbarbutton:not(.wikibase-toolbarbutton-disabled):nth-child(1)")
  link(:save_aliases_disabled,	:css => "div.wb-aliases > div > span.wb-ui-propertyedittool-editablevalue > span > span > span > span > a.wikibase-toolbarbutton-disabled:nth-child(1)")
  link(:cancel_aliases,			:css => "div.wb-aliases > div > span.wb-ui-propertyedittool-editablevalue > span > span > span > span > a.wikibase-toolbarbutton:not(.wikibase-toolbarbutton-disabled):nth-child(2)")
  text_field(:aliases_input_first, :xpath => "//li[contains(@class, 'wb-aliases-alias')]/span/input")
  link(:aliases_input_first_remove, :xpath => "//li[contains(@class, 'wb-aliases-alias')]/a[contains(@class, 'tagadata-close')]")
  text_field(:aliases_input_empty, :xpath => "//li[contains(@class, 'tagadata-choice-empty')]/span/input")
  text_field(:aliases_input_modified, :xpath => "//li[contains(@class, 'tagadata-choice-modified')]/span/input")
  text_field(:aliases_input_equal, :xpath => "//li[contains(@class, 'tagadata-choice-equal')]/span/input")
  link(:aliases_input_remove, :xpath => "//li[contains(@class, 'tagadata-choice-modified')]/a[contains(@class, 'tagadata-close')]")

  # aliases methods
  def count_existing_aliases
    count = 0
    if aliases_list? == false
      return 0
    end
    aliases_list_element.each do |aliasElem|
      count = count+1
    end
    return count
  end

  def get_nth_alias n
    count = 1
    if aliases_list_element.exists?
      aliases_list_element.each do |aliasElem|
        if count == n
          return aliasElem
        end
        count = count+1
      end
    end
    return false
  end

  def add_aliases(aliases)
    add_aliases
    aliases.each do |ali|
      self.aliases_input_empty= ali
    end
    save_aliases
    ajax_wait
    wait_for_api_callback
  end
end
