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
  div(:referenceContainer, :class => "wb-statement-references-container")
  div(:referenceHeading, :class => "wb-statement-references-heading")
  link(:referenceHeadingToggleLink, :css => ".wb-statement-references-heading a")
  div(:referenceListItems, :xpath => "//div[contains(@class, 'wb-statement-references')]/div[contains(@class, 'wb-snaklistview')]")
  div(:reference1Property, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-reference-snaks')]/div[contains(@class, 'wb-snak-property-container')]/div")
  div(:reference2Property, :xpath => "//div[contains(@class, 'wb-referenceview')][2]/div[contains(@class, 'wb-reference-snaks')]/div[contains(@class, 'wb-snak-property-container')]/div")
  div(:reference3Property, :xpath => "//div[contains(@class, 'wb-referenceview')][3]/div[contains(@class, 'wb-reference-snaks')]/div[contains(@class, 'wb-snak-property-container')]/div")
  link(:reference1PropertyLink, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-reference-snaks')]/div[contains(@class, 'wb-snak-property-container')]/div/a")
  div(:reference1Value, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-reference-snaks')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  div(:reference2Value, :xpath => "//div[contains(@class, 'wb-referenceview')][2]/div[contains(@class, 'wb-reference-snaks')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  div(:reference3Value, :xpath => "//div[contains(@class, 'wb-referenceview')][3]/div[contains(@class, 'wb-reference-snaks')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  link(:reference1ValueLink, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-reference-snaks')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  # TODO: could this lead to problems? for CM & item type properties there is an additional "a" element around the textbox; this is not the case for string type properies
  #text_area(:referenceValueInput, :xpath => "//div[contains(@class, 'valueview-ineditmode')]/div/a/textarea[contains(@class, 'valueview-input')]")
  text_area(:referenceValueInput, :class => "valueview-input")
  link(:saveReference, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='save']")
  link(:cancelReference, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='cancel']")
  link(:removeReference, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='remove']")
  link(:addReferenceToFirstClaim, :xpath => "//div[contains(@class, 'wb-statement-references-container')][1]/div[contains(@class, 'wb-statement-references')]/div[contains(@class, 'wb-snaklistview')]/div[contains(@class, 'wb-listview-toolbar')]/div/span/span/a")
  link(:editReference1, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-claim-toolbar')]/span/span/span/span/a[text()='edit']")

  def wait_for_reference_value_box
    wait_until do
      self.referenceValueInput
    end
  end

  def wait_for_referencesToggle
    wait_until do
      referenceListItems_element.visible?
    end
  end

  def add_reference_to_first_claim(property, value)
    addReferenceToFirstClaim
    self.entitySelectorInput = property
    ajax_wait
    wait_for_entity_selector_list
    wait_for_reference_value_box
    self.referenceValueInput = value
    ajax_wait
    saveReference
    ajax_wait
    wait_for_statement_request_finished
  end

end
