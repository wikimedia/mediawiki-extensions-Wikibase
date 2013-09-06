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
  div(:aliasesDiv, :class => "wb-aliases")
  span(:aliasesTitle, :class => "wb-aliases-label")
  unordered_list(:aliasesList, :class => "wb-aliases-container")
  link(:addAliases,	:css => "div.wb-aliases a.wikibase-toolbarbutton-addbutton:not(.wikibase-toolbarbutton-disabled)")
  link(:addAliasesDisabled,	:css => "div.wb-aliases a.wikibase-toolbarbutton-addbutton.wikibase-toolbarbutton-disabled")
  link(:editAliases, :css => "div.wb-aliases a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  link(:saveAliases, :css => "div.wb-aliases a.wikibase-toolbareditgroup-savebutton:not(.wikibase-toolbarbutton-disabled)")
  link(:saveAliasesDisabled, :css => "div.wb-aliases a.wikibase-toolbareditgroup-savebutton.wikibase-toolbarbutton-disabled")
  link(:cancelAliases, :css => "div.wb-aliases a.wikibase-toolbareditgroup-cancelbutton:not(.wikibase-toolbarbutton-disabled)")
  text_field(:aliasesInputFirst, :xpath => "//div[contains(@class, 'wb-ui-aliasesedittool')]//li[contains(@class, 'wb-aliases-alias')]//input")
  link(:aliasesInputFirstRemove, :css => "div.wb-aliases a.tagadata-close")
  text_field(:aliasesInputEmpty, :xpath => "//div[contains(@class, 'wb-ui-aliasesedittool')]//li[contains(@class, 'tagadata-choice-empty')]//input")
  text_field(:aliasesInputModified, :xpath => "//div[contains(@class, 'wb-ui-aliasesedittool')]//li[contains(@class, 'tagadata-choice-modified')]//input")
  text_field(:aliasesInputEqual, :xpath => "//div[contains(@class, 'wb-ui-aliasesedittool')]//li[contains(@class, 'tagadata-choice-equal')]//input")
  link(:aliasesInputRemove, :css => "div.wb-aliases li.tagadata-choice-modified a.tagadata-close")
  span(:aliasesHelpField, :css => "div.wb-aliases span.mw-help-field-hint")

  # aliases methods
  def get_aliases
    aliases = Array.new
    aliasesList_element.each do |aliasElem|
      aliases.push(aliasElem.text)
    end
    aliases
  end

  def alias_exists value
    aliasesList_element.each do |aliasElem|
      if aliasElem.text.eql? value
        return true
      end
    end
    return false
  end

  def count_existing_aliases
    count = 0
    if aliasesList? == false
      return 0
    end
    aliasesList_element.each do |aliasElem|
      count = count+1
    end
    count
  end

  def get_nth_alias n
    count = 1
    if aliasesList_element.exists?
      aliasesList_element.each do |aliasElem|
        if count == n
          return aliasElem
        end
        count = count+1
      end
    end
    false
  end

  def insert_aliases(aliases)
    aliases.each do |ali|
      self.aliasesInputEmpty= ali
    end
  end

  def add_aliases(aliases)
    addAliases
    aliases.each do |ali|
      self.aliasesInputEmpty= ali
    end
    saveAliases
    ajax_wait
    wait_for_api_callback
  end
end
