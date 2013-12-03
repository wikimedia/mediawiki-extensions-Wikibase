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
  h2(:sitelinkHeading, id: "sitelinks-wikipedia")
  table(:sitelinkTable, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]")
  element(:sitelinkTableHeader, :tr, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//tr[contains(@class, 'wb-sitelinks-columnheaders')]")
  element(:sitelinkSitename, :td, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]/tbody/tr/td[1]")
  element(:sitelinkSiteid, :td, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]/tbody/tr/td[2]")
  a(:sitelinkLink, css: "table[data-wb-sitelinks-group='wikipedia'] > tbody > tr > td:nth-child(3) > a")
  span(:sitelinkCounter, class: "wb-ui-propertyedittool-counter")
  text_field(:siteIdInputField, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-sitename')]/input")
  text_field(:pageInputField, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-link')]/input[not(@disabled)]")
  text_field(:pageInputFieldDisabled, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-link')]/input[contains(@disabled, 'disabled')]")
  ul(:siteIdDropdown, xpath: "//ul[contains(@class, 'wikibase-siteselector-list')]")
  li(:siteIdDropdownFirstElement, xpath: "//ul[contains(@class, 'wikibase-siteselector-list')]/li")
  ul(:pageNameDropdown, xpath: "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all ui-suggester-list']")
  li(:pageNameDropdownFirstElement, xpath: "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all ui-suggester-list']/li")
  element(:sitelinkSortLanguage, :th, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//th[contains(@class, 'wb-sitelinks-sitename')]")
  element(:sitelinkSortCode, :th, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//th[contains(@class, 'wb-sitelinks-siteid')]")
  element(:sitelinkSortLink, :th, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//th[contains(@class, 'wb-sitelinks-link')]")

  a(:addSitelinkLink, css: "table[data-wb-sitelinks-group='wikipedia'] a.wb-ui-propertyedittool-toolbarbutton-addbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:addSitelinkLinkDisabled, css: "table[data-wb-sitelinks-group='wikipedia'] a.wb-ui-propertyedittool-toolbarbutton-addbutton.wikibase-toolbarbutton-disabled")
  a(:saveSitelinkLink, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-savebutton:not(.wikibase-toolbarbutton-disabled)")
  a(:saveSitelinkLinkDisabled, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-savebutton.wikibase-toolbarbutton-disabled")
  a(:cancelSitelinkLink, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-cancelbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:removeSitelinkLink, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-removebutton:not(.wikibase-toolbarbutton-disabled)")
  a(:editSitelinkLink, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:editSitelinkLinkDisabled, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-editbutton.wikibase-toolbarbutton-disabled")
  a(:editSitelinkLinkEn, css: "table[data-wb-sitelinks-group='wikipedia'] tr.wb-sitelinks-en a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:editSitelinkLinkEnDisabled, css: "table[data-wb-sitelinks-group='wikipedia'] tr.wb-sitelinks-en a.wikibase-toolbareditgroup-editbutton.wikibase-toolbarbutton-disabled")
  span(:articleTitle, xpath: "//h1[@id='firstHeading']/span")
  span(:sitelinkHelpField, css: "table[data-wb-sitelinks-group='wikipedia'] span.mw-help-field-hint")

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
      wait_for_api_callback
      count = count + 1
    end
  end

  def add_sitelinks(sitelinks)
    sitelinks.each do |sitelink|
      addSitelinkLink
      self.siteIdInputField = sitelink[0]
      self.pageInputField_element.when_visible
      self.pageInputField = sitelink[1]
      saveSitelinkLink
      wait_for_api_callback
    end
  end

  def get_sitelinks_order
    siteids = Array.new
    sitelinkTable_element.each do |row|
      siteids.push(row[1].text)
    end
    siteids.delete_at(0)
    siteids.delete_at(siteids.count-1)

    return siteids
  end

  def set_sitelink_list_to_full
    @browser.execute_script("wb.ui.SiteLinksEditTool.prototype.isFull = function() { return true; };")
  end
end
