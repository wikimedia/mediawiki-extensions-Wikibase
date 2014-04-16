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
  h2(:sitelink_heading, id: "sitelinks-wikipedia")
  table(:sitelink_table, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]")
  element(:sitelink_table_header, :tr, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//tr[contains(@class, 'wb-sitelinks-columnheaders')]")
  element(:sitelink_sitename, :td, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]/tbody/tr/td[1]")
  element(:sitelink_siteid, :td, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]/tbody/tr/td[2]")
  a(:sitelink_link, css: "table[data-wb-sitelinks-group='wikipedia'] > tbody > tr > td:nth-child(3) > a")
  span(:sitelink_counter, class: "wb-ui-propertyedittool-counter")
  text_field(:site_id_input_field, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-sitename')]/input")
  text_field(:page_input_field, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-link')]/input[not(@disabled)]")
  text_field(:page_input_field_disabled, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//td[contains(@class, 'wb-sitelinks-link')]/input[contains(@disabled, 'disabled')]")
  ul(:site_id_dropdown, xpath: "//ul[contains(@class, 'wikibase-siteselector-list')]")
  li(:site_id_dropdown_first_element, xpath: "//ul[contains(@class, 'wikibase-siteselector-list')]/li")
  ul(:page_name_dropdown, css: "ul.ui-suggester-list:not(.ui-entityselector-list):not(.wikibase-siteselector-list)")
  li(:page_name_dropdown_first_element, css: "ul.ui-suggester-list:not(.ui-entityselector-list):not(.wikibase-siteselector-list) li")
  element(:sitelink_sort_language, :th, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//th[contains(@class, 'wb-sitelinks-sitename')]")
  element(:sitelink_sort_code, :th, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//th[contains(@class, 'wb-sitelinks-siteid')]")
  element(:sitelink_sort_link, :th, xpath: "//table[contains(@data-wb-sitelinks-group, 'wikipedia')]//th[contains(@class, 'wb-sitelinks-link')]")

  a(:add_sitelink_link, css: "table[data-wb-sitelinks-group='wikipedia'] a.wb-ui-propertyedittool-toolbarbutton-addbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:add_sitelink_link_disabled, css: "table[data-wb-sitelinks-group='wikipedia'] a.wb-ui-propertyedittool-toolbarbutton-addbutton.wikibase-toolbarbutton-disabled")
  a(:save_sitelink_link, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-savebutton:not(.wikibase-toolbarbutton-disabled)")
  a(:save_sitelink_link_disabled, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-savebutton.wikibase-toolbarbutton-disabled")
  a(:cancel_sitelink_link, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-cancelbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:remove_sitelink_link, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-removebutton:not(.wikibase-toolbarbutton-disabled)")
  a(:edit_sitelink_link, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:edit_sitelink_link_disabled, css: "table[data-wb-sitelinks-group='wikipedia'] a.wikibase-toolbareditgroup-editbutton.wikibase-toolbarbutton-disabled")
  a(:edit_sitelink_link_en, css: "table[data-wb-sitelinks-group='wikipedia'] tr.wb-sitelinks-en a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:edit_sitelink_link_en_disabled, css: "table[data-wb-sitelinks-group='wikipedia'] tr.wb-sitelinks-en a.wikibase-toolbareditgroup-editbutton.wikibase-toolbarbutton-disabled")
  span(:article_title, xpath: "//h1[@id='firstHeading']/span")
  span(:sitelink_help_field, css: "table[data-wb-sitelinks-group='wikipedia'] span.mw-help-field-hint")

  # sitelinks methods
  def count_existing_sitelinks
    if sitelink_table_header? == false
      return 0
    end
    count = 0
    sitelink_table_element.each do |table_row|
      count = count+1
    end
    return count - 2 # subtracting the table header row and the footer row
  end

  def get_number_of_sitelinks_from_counter
    sitelink_counter_element.when_visible

    scanned = sitelink_counter.scan(/\(([^)]+)\)/)
    return scanned[0][0]
  end

  def remove_all_sitelinks
    count = 0
    number_of_sitelinks = count_existing_sitelinks
    while count < (number_of_sitelinks)
      edit_sitelink_link
      remove_sitelink_link
      wait_for_api_callback
      count = count + 1
    end
  end

  def add_sitelinks(sitelinks)
    sitelinks.each do |sitelink|
      add_sitelink_link
      self.site_id_input_field = sitelink[0]
      self.page_input_field_element.when_visible
      self.page_input_field = sitelink[1]
      wait_for_api_callback
      save_sitelink_link
      wait_for_api_callback
    end
  end

  def get_sitelinks_order
    siteids = Array.new
    sitelink_table_element.each do |row|
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
