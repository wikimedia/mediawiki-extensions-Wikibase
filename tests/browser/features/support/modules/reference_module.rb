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
  div(:reference_container, class: "wb-statement-references")
  div(:reference_heading, class: "wb-statement-references-heading")
  a(:reference_heading_toggle_link, css: ".wb-statement-references-heading a")
  div(:reference_edit_heading, xpath: "//div[contains(@class, 'wb-referenceview')]/div[contains(@class, 'wb-snaklistview-heading')]")
  div(:reference_list_items, xpath: "//div[contains(@class, 'wb-statement-references')]/div[contains(@class, 'wb-listview')]")
  div(:reference1Property, xpath: "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')][1]/div[contains(@class, 'wb-snak-property-container')]/div")
  div(:reference1Property2, xpath: "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')][2]/div[contains(@class, 'wb-snak-property-container')]/div")
  div(:reference2Property, xpath: "//div[contains(@class, 'wb-referenceview')][2]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-property-container')]/div")
  div(:reference3Property, xpath: "//div[contains(@class, 'wb-referenceview')][3]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-property-container')]/div")
  a(:reference1Property_link, xpath: "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')][1]/div[contains(@class, 'wb-snak-property-container')]/div/a")
  a(:reference1Property_link2, xpath: "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')][2]/div[contains(@class, 'wb-snak-property-container')]/div/a")
  div(:reference1Value, xpath: "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')][1]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  div(:reference1Value2, xpath: "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')][2]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  div(:reference2Value, xpath: "//div[contains(@class, 'wb-referenceview')][2]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  div(:reference3Value, xpath: "//div[contains(@class, 'wb-referenceview')][3]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  a(:reference1Value_link, xpath: "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')][1]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  a(:reference1Value_link2, xpath: "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')][2]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  # TODO: could this lead to problems? for CM & item type properties there is an additional "a" element around the textbox; this is not the case for string type properies
  #textarea(:reference_value_input, xpath: "//div[contains(@class, 'valueview-ineditmode')]/div/a/textarea[contains(@class, 'valueview-input')]")
  textarea(:reference_value_input, xpath: "//div[contains(@class, 'wb-claimlistview')]//textarea[contains(@class, 'valueview-input')]", index: 0)
  textarea(:reference_value_input2, xpath: "//div[contains(@class, 'wb-claimlistview')]//textarea[contains(@class, 'valueview-input')]", index: 1)
  a(:save_reference, xpath: "//div[contains(@class, 'wb-referenceview')]/span[contains(@class, 'wb-edittoolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='save']")
  a(:cancel_reference, xpath: "//div[contains(@class, 'wb-referenceview')]/span[contains(@class, 'wb-edittoolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='cancel']")
  a(:remove_reference, xpath: "//div[contains(@class, 'wb-referenceview')]/span[contains(@class, 'wb-edittoolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='remove']")
  a(:remove_reference_line1, xpath: "//div[contains(@class, 'wb-referenceview')]/div[contains(@class, 'wb-snaklistview-listview')]/div[contains(@class, 'wb-snakview')][1]/span[contains(@class, 'wb-removetoolbar')]/div/span/span/a[text()='remove']")
  a(:remove_reference_line2, xpath: "//div[contains(@class, 'wb-referenceview')]/div[contains(@class, 'wb-snaklistview-listview')]/div[contains(@class, 'wb-snakview')][2]/span[contains(@class, 'wb-removetoolbar')]/div/span/span/a[text()='remove']")
  a(:add_reference_line, xpath: "//div[contains(@class, 'wb-referenceview')]/span[contains(@class, 'wb-addtoolbar')]/div/span/span/a[text()='add']")
  a(:add_reference_to_first_claim, xpath: "//div[contains(@class, 'wb-statement-references-container')][1]/div[contains(@class, 'wb-statement-references')]/span[contains(@class, 'wb-addtoolbar')]/div/span/span/a")
  a(:edit_reference1, xpath: "//div[contains(@class, 'wb-referenceview')][1]/span[contains(@class, 'wb-edittoolbar')]/span/span/span/span/a[text()='edit']")

  def wait_for_reference_value_box
    wait_until do
      self.reference_value_input?
    end
  end

  def toggle_reference_section
    reference_heading_toggle_link
    sleep 0.5
    wait_until do
      reference_list_items_element.visible?
    end
  end

  def add_reference_to_first_claim(property, value)
    add_reference_to_first_claim
    self.entity_selector_input = property
    ajax_wait
    wait_for_entity_selector_list
    wait_for_reference_value_box
    self.reference_value_input = value
    ajax_wait
    save_reference
    ajax_wait
    wait_for_statement_request_finished
  end

end
