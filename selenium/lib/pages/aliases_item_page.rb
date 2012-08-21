# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for aliases

require 'ruby_selenium'

# deriving from SitelinksItemPage to have all accessors available
# we should think of moving all accessors to some base class (e.g. ItemPage)
# to have them on one place and available at the same time
class AliasesItemPage < SitelinksItemPage
  include PageObject
  # aliases UI
  div(:aliasesDiv, :class => "wb-aliases")
  span(:aliasesTitle, :class => "wb-aliases-label")
  unordered_list(:aliasesList, :class => "wb-aliases-container")
  div(:addAliasesDiv, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool']/div")
  div(:addAliasesDivInEditMode, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool wb-ui-propertyedittool-ineditmode']/div")
  link(:addAliases, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool']/div/div/a[text()='add']")
  span(:addAliasesDisabled, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool']/div/div/span")
  link(:editAliases, :xpath => "//div[contains(@class, 'wb-aliases')]/div/span[contains(@class, 'wb-ui-propertyedittool-editablevalue')]/span/div/div/div/a[text()='edit']")
  link(:saveAliases, :xpath => "//div[contains(@class, 'wb-aliases')]/div/span[contains(@class, 'wb-ui-propertyedittool-editablevalue')]/span/div/div/div/a[text()='save']")
  link(:cancelAliases, :xpath => "//div[contains(@class, 'wb-aliases')]/div/span[contains(@class, 'wb-ui-propertyedittool-editablevalue')]/span/div/div/div/a[text()='cancel']")
  text_field(:aliasesInputFirst, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all wb-aliases-alias']/span/input")
  link(:aliasesInputFirstRemove, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all wb-aliases-alias']/a[@class='tagadata-close']")
  text_field(:aliasesInputEmpty, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-empty']/span/input")
  text_field(:aliasesInputModified, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-modified']/span/input")
  text_field(:aliasesInputEqual, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-equal']/span/input")
  link(:aliasesInputRemove, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-modified']/a[@class='tagadata-close']")
  def wait_for_aliases_to_load
    wait_until do
      aliasesDiv?
    end
  end

  def countExistingAliases
    count = 0
    aliasesList_element.each do |aliasElem|
      count = count+1
    end
    return count
  end

  def getNthAlias n
    count = 1
    if aliasesList_element.exists?
      aliasesList_element.each do |aliasElem|
        if count == n
          return aliasElem
        end
        count = count+1
      end
    end
    return false
  end
end
