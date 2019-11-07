# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for reference page object

module ReferencePage
  include PageObject
  # references UI elements
  a(:add_reference, css: 'div.wikibase-statementview-references > div.wikibase-addtoolbar-container span.wikibase-toolbar-button-add:not(.wikibase-toolbarbutton-disabled) > a')
  a(:add_reference_disabled, css: 'div.wikibase-statementview-references span.wikibase-toolbar-button-add.wikibase-toolbarbutton-disabled > a')
  a(:remove_reference, css: 'div.wikibase-statementview-references .wikibase-removetoolbar-container span.wikibase-toolbar-button-remove:not(.wikibase-toolbarbutton-disabled) > a')
  a(:remove_reference_disabled, css: 'div.wikibase-statementview-references .wikibase-removetoolbar-container span.wikibase-toolbar-button-remove.wikibase-toolbarbutton-disabled > a')
  a(:add_reference_snak, css: 'div.wikibase-statementview-references div.wikibase-referenceview span.wikibase-toolbar-button-add:not(.wikibase-toolbarbutton-disabled) > a')
  a(:add_reference_snak_disabled, css: 'div.wikibase-statementview-references div.wikibase-referenceview span.wikibase-toolbar-button-add.wikibase-toolbarbutton-disabled > a')
  span(:reference_counter, css: 'div.wikibase-statementview-references-heading span.ui-toggler-label')
  a(:first_suggested_entity_link, xpath: '//ul[contains(@class, "ui-entityselector-list")][not(contains(@style,"display: none"))][not(contains(@class, "wikibase-entitysearch-list"))]//a')

  def remove_reference_snak(snak_index = 1)
    element('a', css: "div.wikibase-statementview-references div.wikibase-referenceview div.wikibase-snaklistview:nth-child(#{snak_index}) span.wikibase-toolbar-button-remove:not(.wikibase-toolbarbutton-disabled) > a")
  end

  def remove_reference_snak_disabled(snak_index = 1)
    element('a', css: "div.wikibase-statementview-references div.wikibase-referenceview div.wikibase-snaklistview:nth-child(#{snak_index}) span.wikibase-toolbar-button-remove.wikibase-toolbarbutton-disabled > a")
  end

  def reference_snak_property(reference_index = 1, snak_index = 1)
    element('div', css: "div.wikibase-statementview-references div.wikibase-referenceview:nth-child(#{reference_index}) div.wikibase-snaklistview:nth-child(#{snak_index}) div.wikibase-snakview-property")
  end

  def reference_snak_property_link(reference_index = 1, snak_index = 1)
    element('a', css: "div.wikibase-statementview-references div.wikibase-referenceview:nth-child(#{reference_index}) div.wikibase-snaklistview:nth-child(#{snak_index}) div.wikibase-snakview-property a")
  end

  def reference_snak_value(reference_index = 1, snak_index = 1)
    element('div', css: "div.wikibase-statementview-references div.wikibase-referenceview:nth-child(#{reference_index}) div.wikibase-snaklistview:nth-child(#{snak_index}) div.wikibase-snakview-value")
  end

  def toggle_references(statement_index = 1)
    element('a', css: "div.wikibase-statementview:nth-child(#{statement_index}) div.wikibase-statementview-references-heading a.ui-toggler")
  end

  def number_of_references_from_counter
    scanned = reference_counter.scan(/([^ ]+)/)
    scanned[0][0]
  end

  def wait_for_reference_save_button
    save_reference_element.when_visible
  end

  def add_reference_snaks(snaks, properties)
    index = 1
    add_reference
    snaks.each do |snak|
      if index > 1
        add_reference_snak
      end
      property_handle = snak[0]
      value = snak[1]
      ajax_wait
      snak_entity_selector_input(index).when_visible.clear
      snak_entity_selector_input(index).send_keys properties[property_handle]['label']
      ajax_wait
      first_suggested_entity_link_element.when_visible.click
      snak_value_input_field(index).when_visible.clear
      snak_value_input_field(index).send_keys value
      ajax_wait
      index += 1
    end
  end
end
