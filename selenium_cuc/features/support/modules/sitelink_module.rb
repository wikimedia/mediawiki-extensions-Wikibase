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
  table(:sitelinkTable, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]")
  element(:sitelinkTableHeader, :tr, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//tr[contains(@class, 'wb-sitelinks-columnheaders')]")
  element(:sitelinkSitename, :td, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]/tbody/tr/td[1]")
  element(:sitelinkSiteid, :td, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]/tbody/tr/td[2]")
  link(:sitelinkLink, :css => "table[data-wb-sitelinks-group='wikipedia'] > tbody > tr > td:nth-child(3) > a")
  span(:sitelinkCounter, :class => "wb-ui-propertyedittool-counter")
  text_field(:siteIdInputField, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-sitename')]/input")
  text_field(:pageInputField, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-link')]/input[not(@disabled)]")
  text_field(:pageInputFieldDisabled, :xpath => "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-link')]/input[contains(@disabled, 'disabled')]")
  unordered_list(:siteIdDropdown, :xpath => "//ul[contains(@class, 'wikibase-siteselector-list')]")
  list_item(:siteIdDropdownFirstElement, :xpath => "//ul[contains(@class, 'wikibase-siteselector-list')]/li")
  unordered_list(:pageNameDropdown, :xpath => "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all ui-suggester-list']")
  list_item(:pageNameDropdownFirstElement, :xpath => "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all ui-suggester-list']/li")

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
  span(:articleTitle, :xpath => "//h1[@id='firstHeading']/span")
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
    sitelinkCounter_element.when_visible

    scanned = sitelinkCounter.scan(/\(([^)]+)\)/)
    return scanned[0][0]
  end

  def remove_all_sitelinks
    count = 0
    number_of_sitelinks = count_existing_sitelinks
    while count < (number_of_sitelinks)
      editSitelinkLink
      removeSitelinkLink
      ajax_wait
      wait_for_api_callback
      count = count + 1
    end
  end
end
