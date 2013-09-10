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
  h2(:sitelinkHeading, :id => "sitelinks-wikipedia")
  #h2(:sitelinkHeadingWikivoyage, :id => "sitelinks-wikivoyage")
  table(:sitelinkTable, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]")
  #table(:sitelinkTableWikivoyage, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikivoyage')]")
  element(:sitelinkTableHeader, :tr, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//tr[contains(@class, 'wb-sitelinks-columnheaders')]")
  #element(:sitelinksHeaderLanguage, :th, :class => "wb-sitelinks-sitename")
  #element(:sitelinksHeaderCode, :th, :class => "wb-sitelinks-siteid")
  #element(:sitelinksHeaderLink, :th, :class => "wb-sitelinks-link")
  #element(:sitelinksTableBody, :tbody, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody")
  #element(:sitelinksTableLanguage, :td, :index => 1)
  span(:sitelinkCounter, :class => "wb-ui-propertyedittool-counter")
  text_field(:siteIdInputField, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-sitename')]/input")
  text_field(:pageInputField, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-link')]/input")
  text_field(:pageInputFieldDisabled, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-link')]/input[contains(@disabled, 'disabled')]")
  #text_field(:pageInputFieldExistingSiteLink, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody/tr/td[contains(@class, 'wb-sitelinks-link')]/input")
  #unordered_list(:siteIdAutocompleteList, :class => "wikibase-siteselector-list")

  #unordered_list(:pageAutocompleteList, :xpath => "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all ui-suggester-list']")
  #unordered_list(:editSitelinkAutocompleteList, :xpath => "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all ui-suggester-list']")
  link(:addSitelinkLink, :css => "table[data-wb-sitelinks-group='wikipedia'] a.wb-ui-propertyedittool-toolbarbutton-addbutton:not(.wikibase-toolbarbutton-disabled)")
  link(:addSitelinkLinkDisabled, :css => "table[data-wb-sitelinks-group='wikipedia'] a.wb-ui-propertyedittool-toolbarbutton-addbutton.wikibase-toolbarbutton-disabled")
  link(:saveSitelinkLink, :css => "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-savebutton:not(.wikibase-toolbarbutton-disabled)")
  link(:saveSitelinkLinkDisabled, :css => "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-savebutton.wikibase-toolbarbutton-disabled")
  link(:cancelSitelinkLink, :css => "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-cancelbutton:not(.wikibase-toolbarbutton-disabled)")
  link(:removeSitelinkLink, :css => "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-removebutton:not(.wikibase-toolbarbutton-disabled)")
  link(:editSitelinkLink, :css => "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  link(:editSitelinkLinkDisabled, :css => "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-editbutton.wikibase-toolbarbutton-disabled")
  link(:editSitelinkLinkEn, :css => "table[data-wb-sitelinks-group='wikipedia'] tr.wb-sitelinks-en a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  link(:editSitelinkLinkEnDisabled, :css => "table[data-wb-sitelinks-group='wikipedia'] tr.wb-sitelinks-en a.wikibase-toolbareditgroup-editbutton.wikibase-toolbarbutton-disabled")
  #link(:pageArticleNormalized, :css => "td.wb-sitelinks-link-sr > a")
  #link(:germanSitelink, :xpath => "//td[contains(@class, 'wb-sitelinks-link-de')]/a")
  #link(:englishSitelink, :xpath => "//td[contains(@class, 'wb-sitelinks-link-en')]/a")
  #span(:articleTitle, :xpath => "//h1[@id='firstHeading']/span")
  span(:sitelinkHelpField, :css => "table[data-wb-sitelinks-group='wikipedia'] span.mw-help-field-hint")

  # sitelinks methods
  def count_existing_sitelinks
    if sitelinkTableHeader? == false
      return 0
    end
    count = 0
    sitelinkTable_element.each do |tableRow|
      count = count+1
    end
    return count - 2 # subtracting the table header row and the footer row
  end

  def get_number_of_sitelinks_from_counter
    siteLinkCounter_element.when_visible

    scanned = siteLinkCounter.scan(/\(([^)]+)\)/)
    return scanned[0][0]
    #integerValue = scanned[0][0].to_i()
    #return integerValue
  end

  ######################### not used #################################
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

  def add_sitelinks(sitelinks)
    sitelinks.each do |sitelink|
      addSitelinkLink
      self.siteIdInputField= sitelink[0]
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
