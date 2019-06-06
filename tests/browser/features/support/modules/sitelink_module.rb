# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for sitelinks page

module SitelinkPage
  include PageObject
  include BadgeSelectorPage

  # sitelinks UI elements
  h3(:sitelink_heading, id: 'sitelinks-wikipedia')
  div(:sitelink_table, css: "div[data-wb-sitelinks-group='wikipedia']")
  span(:sitelink_siteid, css: "div[data-wb-sitelinks-group='wikipedia'] ul.wikibase-sitelinklistview-listview span.wikibase-sitelinkview-siteid")
  ul(:sitelink_list, css: "div[data-wb-sitelinks-group='wikipedia'] ul.wikibase-sitelinklistview-listview")
  a(:sitelink_link, css: "div[data-wb-sitelinks-group='wikipedia'] ul.wikibase-sitelinklistview-listview span.wikibase-sitelinkview-page a")
  span(:sitelink_counter, class: 'wikibase-sitelinkgroupview-counter')
  text_field(:page_input_field_disabled, xpath: "//div[contains(@data-wb-sitelinks-group, 'wikipedia')]//table//td[contains(@class, 'wikibase-sitelinkview-link')]//input[@disabled]")
  ul(:site_id_dropdown, xpath: "//ul[contains(@class, 'wikibase-siteselector-list')]")
  li(:site_id_dropdown_first_element, xpath: "//ul[contains(@class, 'wikibase-siteselector-list')]/li")
  ul(:page_name_dropdown, css: 'ul.ui-suggester-list:not(.ui-entityselector-list):not(.wikibase-siteselector-list)')
  li(:page_name_dropdown_first_element, css: 'ul.ui-suggester-list:not(.ui-entityselector-list):not(.wikibase-siteselector-list) li')

  a(:remove_sitelink_link, css: "div[data-wb-sitelinks-group='wikipedia'] ul span.wikibase-toolbar-button-remove:not(.wikibase-toolbarbutton-disabled) > a")
  a(:remove_sitelink_link_disabled, css: "div[data-wb-sitelinks-group='wikipedia'] ul span.wikibase-toolbar-button-remove.wikibase-toolbarbutton-disabled > a")
  a(:save_sitelink_link, css: "div[data-wb-sitelinks-group='wikipedia'] .wikibase-toolbar-container span.wikibase-toolbar-button-save:not(.wikibase-toolbarbutton-disabled) > a")
  a(:save_sitelink_link_disabled, css: "div[data-wb-sitelinks-group='wikipedia'] .wikibase-toolbar-container span.wikibase-toolbar-button-save.wikibase-toolbarbutton-disabled > a")
  a(:cancel_sitelink_link, css: "div[data-wb-sitelinks-group='wikipedia'] .wikibase-toolbar-container span.wikibase-toolbar-button-cancel:not(.wikibase-toolbarbutton-disabled) > a")
  a(:edit_sitelink_link, css: "div[data-wb-sitelinks-group='wikipedia'] .wikibase-toolbar-container span.wikibase-toolbar-button-edit:not(.wikibase-toolbarbutton-disabled) > a")
  a(:edit_sitelink_link_disabled, css: "div[data-wb-sitelinks-group='wikipedia'] .wikibase-toolbar-container wikibase-toolbar-button-edit.wikibase-toolbarbutton-disabled > a")
  a(:edit_sitelink_link_en, css: "div[data-wb-sitelinks-group='wikipedia'] table tr.wikibase-sitelinkview-enwiki span.wikibase-toolbar-button-edit:not(.wikibase-toolbarbutton-disabled) > a")
  a(:edit_sitelink_link_en_disabled, css: "div[data-wb-sitelinks-group='wikipedia'] table tr.wikibase-sitelinkview-enwiki span.wikibase-toolbar-button-edit.wikibase-toolbarbutton-disabled > a")
  h1(:article_title, xpath: "//h1[contains(@class, 'firstHeading')]")
  span(:sitelink_help_field, css: "div[data-wb-sitelinks-group='wikipedia'] .wikibase-toolbar-container span.wb-help-field-hint")

  indexed_property(:sitelinks_form, [
    [:text_field, :site_id_input_field, { css: "div[data-wb-sitelinks-group='wikipedia'] ul li:nth-child(%s) span.wikibase-sitelinkview-siteid input" }],
    [:text_field, :page_input_field, { css: "div[data-wb-sitelinks-group='wikipedia'] ul li:nth-child(%s) span.wikibase-sitelinkview-link input:not(.wikibase-pagesuggester-disabled)" }],
    [:text_field, :page_input_field_disabled, { css: "div[data-wb-sitelinks-group='wikipedia'] ul li:nth-child(%s) span.wikibase-sitelinkview-link input.wikibase-pagesuggester-disabled" }],
    [:span, :badge_selector, { css: "div[data-wb-sitelinks-group='wikipedia'] ul li:nth-child(%s) span.wikibase-badgeselector" }],
    [:span, :empty_badge, { css: "div[data-wb-sitelinks-group='wikipedia'] ul li:nth-child(%s) span.wb-badge-empty" }]
  ])

  indexed_property(:sitelinks_sections, [
    [:div, :section_div, { css: "div[data-wb-sitelinks-group='%s']" }],
    [:div, :error_message, { css: "div[data-wb-sitelinks-group='%s'] div.wb-error div.wb-error-message" }]
  ])

  # sitelinks methods
  def count_existing_sitelinks
    if sitelink_list? == false
      return 0
    end
    count = 0
    sitelink_list_element.each { count += 1 }
    count
  end

  def number_of_sitelinks_from_counter
    sitelink_counter_element.when_visible

    scanned = sitelink_counter.scan(/\(([^ ]+)\)/)
    scanned[0][0]
  end

  def remove_all_sitelinks
    count = 0
    number_of_sitelinks = count_existing_sitelinks
    edit_sitelink_link
    while count < (number_of_sitelinks)
      remove_sitelink_link
      wait_for_api_callback
      count += 1
    end
    save_sitelink_link
    wait_for_api_callback
  end

  def add_sitelinks(sitelinks)
    edit_sitelink_link
    index = 1
    sitelinks.each do |sitelink|
      insert_site(index, sitelink[0])
      insert_page(index, sitelink[1])
      index += 1
    end
    save_sitelink_link_element.when_visible.click
    wait_for_api_callback
  end

  def insert_site(index, site)
    sitelinks_form[index].site_id_input_field_element.when_present.value = site
  end

  def insert_page(index, page)
    sitelinks_form[index].page_input_field_element.when_present.value = page
  end

  def set_sitelink_list_to_full
    execute_script('$.wikibase.sitelinklistview.prototype.isFull = function() { return true; };')
  end
end
