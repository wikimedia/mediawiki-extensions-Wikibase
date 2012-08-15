# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for sitelinks

require 'ruby_selenium'

class SitelinksItemPage < NewItemPage
  include PageObject

  # language links UI
  table(:sitelinksTable, :class => "wb-sitelinks")
  link(:addSitelinkLink, :css => "table.wb-sitelinks > tfoot > tr > td > div.wb-ui-toolbar > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  span(:siteLinkCounter, :class => "wb-ui-propertyedittool-counter")
  text_field(:siteIdInputField, :xpath => "//div[@id='mw-content-text']/table/tbody/tr/td[1]/input")
  text_field(:pageInputField, :xpath => "//div[@id='mw-content-text']/table/tbody/tr/td[2]/input")
  text_field(:siteIdInputFieldLoading, :xpath => "//div[@id='mw-content-text']/table/tbody/tr/td[1]/input[@class='ui-autocomplete-loading']")
  text_field(:pageInputFieldLoading, :xpath => "//div[@id='mw-content-text']/table/tbody/tr/td[2]/input[@class='ui-autocomplete-loading']")
  span(:saveSitelinkLinkDisabled, :class => "wb-ui-toolbar-button-disabled")
  unordered_list(:siteIdAutocompleteList, :class => "ui-autocomplete", :index => 0)
  unordered_list(:pageAutocompleteList, :class => "ui-autocomplete", :index => 1)
  unordered_list(:editSitelinkAutocompleteList, :class => "ui-autocomplete", :index => 0)
  link(:saveSitelinkLink, :text => "save")
  link(:cancelSitelinkLink, :text => "cancel")
  link(:removeSitelinkLink, :xpath => "//td[@class='wb-ui-propertyedittool-editablevalue-toolbarparent']/div/div/div/a[2]")
  link(:editSitelinkLink, :xpath => "//td[@class='wb-ui-propertyedittool-editablevalue-toolbarparent']/div/div/div/a")
  link(:pageArticleNormalized, :css => "td.wb-sitelinks-link-sr > a")
  link(:germanSitelink, :xpath => "//td[@class='wb-sitelinks-link wb-sitelinks-link-de']/a")
  span(:articleTitle, :xpath => "//h1[@id='firstHeading']/span")
  def getNumberOfSitelinksFromCounter
    wait_until do
      siteLinkCounter?
    end
    scanned = siteLinkCounter.scan(/\(([^)]+)\)/)
    integerValue = scanned[0][0].to_i()
    return integerValue
  end

  def countAutocompleteListElements(list)
    count = 0
    list.each do |listItem|
      count = count+1
    end
    return count
  end

  def getNthElementInAutocompleteList(list, n)
    count = 0
    list.each do |listItem|
      count = count+1
      if count == n
        return listItem
      end
    end
    return false
  end

  def getNthSitelinksTableRow(n)
    count = 0
    sitelinksTable_element.each do |tableRow|
      #don't count here to skip the table header
      #count = count+1
      if count == n
        return tableRow
      end
      #count here instead
      count = count+1
    end
    return false
  end

  def countExistingSitelinks
    count = 0
    sitelinksTable_element.each do |tableRow|
      count = count+1
    end
    return count-2
  end

  def wait_for_sitelinks_to_load
    wait_until do
      sitelinksTable?
    end
  end

  def add_sitelink(lang_code, article_title)
    addSitelinkLink
    self.siteIdInputField= lang_code
    self.pageInputField= article_title
    saveSitelinkLink
    ajax_wait
    wait_for_api_callback
  end

  def remove_all_sitelinks
    count = 0
    number_of_sitelinks = getNumberOfSitelinksFromCounter
    while count < (number_of_sitelinks)
      removeSitelinkLink
      ajax_wait
      wait_for_api_callback
      count = count + 1
    end
  end

end
