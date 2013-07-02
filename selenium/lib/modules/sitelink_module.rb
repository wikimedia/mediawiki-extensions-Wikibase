# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for sitelinks page

module SitelinkPage
  include PageObject
  # sitelinks UI elements
  table(:sitelinksTable, :class => "wb-sitelinks")
  element(:sitelinksTableColumnHeaders, :tr, :class => "wb-sitelinks-columnheaders")
  element(:sitelinksHeaderLanguage, :th, :class => "wb-sitelinks-sitename")
  element(:sitelinksHeaderCode, :th, :class => "wb-sitelinks-siteid")
  element(:sitelinksHeaderLink, :th, :class => "wb-sitelinks-link")
  element(:sitelinksTableBody, :tbody, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody")
  element(:sitelinksTableLanguage, :td, :index => 1)
  link(:addSitelinkLink, :css => "table.wb-sitelinks > tfoot > tr > td > span.wb-ui-toolbar > span.wb-ui-toolbar-group > a.wikibase-wbbutton:not(.wikibase-wbbutton-disabled):nth-child(1)")
  span(:siteLinkCounter, :class => "wb-ui-propertyedittool-counter")
  text_field(:siteIdInputField, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tfoot/tr/td[1]/input")
  text_field(:pageInputField, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tfoot/tr/td[contains(@class, 'wb-sitelinks-link')]/input")
  text_field(:pageInputFieldExistingSiteLink, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody/tr/td[contains(@class, 'wb-sitelinks-link')]/input")
  unordered_list(:siteIdAutocompleteList, :class => "wikibase-siteselector-list")
  #todo: this is not a nice way to get the suggestion list, we should find a better way
  unordered_list(:pageAutocompleteList, :xpath => "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all ui-suggester-list']")
  unordered_list(:editSitelinkAutocompleteList, :xpath => "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all ui-suggester-list']")
  link(:saveSitelinkLink, :xpath => "//a[not(contains(@class, 'wikibase-wbbutton-disabled'))][text()='save']")
  link(:saveSitelinkLinkDisabled, :xpath => "//a[contains(@class, 'wikibase-wbbutton-disabled')][text()='save']")
  link(:cancelSitelinkLink, :xpath => "//a[not(contains(@class, 'wikibase-wbbutton-disabled'))][text()='cancel']")
  link(:removeSitelinkLink, :xpath => "//a[not(contains(@class, 'wikibase-wbbutton-disabled'))][text()='remove']")
  link(:editSitelinkLink, :css => "td.wb-ui-propertyedittool-editablevalue-toolbarparent > span > span > span > a:not(.wikibase-wbbutton-disabled)")
  link(:englishEditSitelinkLink, :xpath => "//tr[contains(@class, 'wb-sitelinks-en')]/td[4]/span/span/span/a")
  link(:pageArticleNormalized, :css => "td.wb-sitelinks-link-srwiki > a")
  link(:germanSitelink, :xpath => "//td[contains(@class, 'wb-sitelinks-link-dewiki')]/a")
  link(:englishSitelink, :xpath => "//td[contains(@class, 'wb-sitelinks-link-enwiki')]/a")
  span(:articleTitle, :xpath => "//h1[@id='firstHeading']/span")
  # sitelinks methods
  def get_number_of_sitelinks_from_counter
    wait_until do
      siteLinkCounter?
    end
    scanned = siteLinkCounter.scan(/\(([^)]+)\)/)
    integerValue = scanned[0][0].to_i()
    return integerValue
  end

  def get_nth_element_in_autocomplete_list(list, n)
    count = 0
    list.each do |listItem|
      count = count+1
      if count == n
        return listItem
      end
    end
    return false
  end

  def get_text_from_sitelist_table(x, y)
    return sitelinksTable_element[x][y].text
  end

  def count_existing_sitelinks
    if sitelinksTableColumnHeaders? == false
      return 0
    end
    count = 0
    sitelinksTable_element.each do |tableRow|
      count = count+1
    end
    return count - 2 # subtracting the table header row and the footer row
  end

  def add_sitelinks(sitelinks)
    sitelinks.each do |sitelink|
      addSitelinkLink
      self.siteIdInputField= sitelink[0]
      self.siteIdInputField_element.send_keys :arrow_right
      wait_until do
        self.pageInputField_element.enabled?
      end
      self.pageInputField= sitelink[1]
      saveSitelinkLink
      ajax_wait
      wait_for_api_callback
    end
  end

  def remove_all_sitelinks
    count = 0
    number_of_sitelinks = get_number_of_sitelinks_from_counter
    while count < (number_of_sitelinks)
      editSitelinkLink
      removeSitelinkLink
      ajax_wait
      wait_for_api_callback
      count = count + 1
    end
  end
end
