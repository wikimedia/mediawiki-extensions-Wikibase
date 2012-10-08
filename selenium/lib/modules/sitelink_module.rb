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
  link(:addSitelinkLink, :css => "table.wb-sitelinks > tfoot > tr > td > div.wb-ui-toolbar > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  span(:siteLinkCounter, :class => "wb-ui-propertyedittool-counter")
  text_field(:siteIdInputField, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody/tr/td[1]/input")
  text_field(:pageInputField, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody/tr/td[contains(@class, 'wb-sitelinks-link')]/input")
  span(:saveSitelinkLinkDisabled, :class => "wb-ui-toolbar-button-disabled")
  unordered_list(:siteIdAutocompleteList, :class => "ui-autocomplete", :index => 0)
  unordered_list(:pageAutocompleteList, :class => "ui-autocomplete", :index => 1)
  unordered_list(:editSitelinkAutocompleteList, :class => "ui-autocomplete", :index => 0)
  link(:saveSitelinkLink, :text => "save")
  link(:cancelSitelinkLink, :text => "cancel")
  link(:removeSitelinkLink, :xpath => "//td[contains(@class, 'wb-ui-propertyedittool-editablevalue-toolbarparent')]/div/div/div/a[2]")
  link(:editSitelinkLink, :xpath => "//td[contains(@class, 'wb-ui-propertyedittool-editablevalue-toolbarparent')]/div/div/div/a")
  link(:englishEditSitelinkLink, :xpath => "//tr[contains(@class, 'wb-sitelinks-en')]/td[4]/div/div/div/a")
  link(:pageArticleNormalized, :css => "td.wb-sitelinks-link-sr > a")
  link(:germanSitelink, :xpath => "//td[contains(@class, 'wb-sitelinks-link-de')]/a")
  link(:englishSitelink, :xpath => "//td[contains(@class, 'wb-sitelinks-link-en')]/a")
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
      removeSitelinkLink
      ajax_wait
      wait_for_api_callback
      count = count + 1
    end
  end
end
