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
  link(:editAliases, :css => "div.wb-aliases > div > span.wb-ui-propertyedittool-editablevalue > span > span > span > span > a.wikibase-toolbarbutton:not(.wikibase-toolbarbutton-disabled):nth-child(1)")
  link(:saveAliases, :css => "div.wb-aliases > div > span.wb-ui-propertyedittool-editablevalue > span > span > span > span > a.wikibase-toolbarbutton:not(.wikibase-toolbarbutton-disabled):nth-child(1)")
  link(:saveAliasesDisabled, :css => "div.wb-aliases > div > span.wb-ui-propertyedittool-editablevalue > span > span > span > span > a.wikibase-toolbarbutton-disabled:nth-child(1)")
  link(:cancelAliases, :css => "div.wb-aliases > div > span.wb-ui-propertyedittool-editablevalue > span > span > span > span > a.wikibase-toolbarbutton:not(.wikibase-toolbarbutton-disabled):nth-child(2)")
  text_field(:aliasesInputFirst, :xpath => "//li[contains(@class, 'wb-aliases-alias')]/span/input")
  link(:aliasesInputFirstRemove, :xpath => "//li[contains(@class, 'wb-aliases-alias')]/a[contains(@class, 'tagadata-close')]")
  text_field(:aliasesInputEmpty, :xpath => "//li[contains(@class, 'tagadata-choice-empty')]/span/input")
  text_field(:aliasesInputModified, :xpath => "//li[contains(@class, 'tagadata-choice-modified')]/span/input")
  text_field(:aliasesInputEqual, :xpath => "//li[contains(@class, 'tagadata-choice-equal')]/span/input")
  link(:aliasesInputRemove, :xpath => "//li[contains(@class, 'tagadata-choice-modified')]/a[contains(@class, 'tagadata-close')]")
  span(:aliasesHelpField, :css => "div.wb-aliases span.mw-help-field-hint")

  # aliases methods
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
