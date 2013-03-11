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
  div(:referenceListItems, :xpath => "//div[contains(@class, 'wb-statement-references')]/div[contains(@class, 'wb-listview')]")
  div(:reference1Property, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-property-container')]/div")
  div(:reference2Property, :xpath => "//div[contains(@class, 'wb-referenceview')][2]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-property-container')]/div")
  div(:reference3Property, :xpath => "//div[contains(@class, 'wb-referenceview')][3]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-property-container')]/div")
  link(:reference1PropertyLink, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-property-container')]/div/a")
  div(:reference1Value, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  div(:reference2Value, :xpath => "//div[contains(@class, 'wb-referenceview')][2]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  div(:reference3Value, :xpath => "//div[contains(@class, 'wb-referenceview')][3]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div")
  link(:reference1ValueLink, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-listview')]/div[contains(@class, 'wb-snakview')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  text_area(:referenceValueInput, :xpath => "//div[contains(@class, 'valueview-ineditmode')]/div/a/textarea[contains(@class, 'valueview-input')]")
  link(:saveReference, :xpath => "//div[contains(@class, 'wb-snaklistview-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='save']")
  link(:cancelReference, :xpath => "//div[contains(@class, 'wb-snaklistview-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='cancel']")
  link(:removeReference, :xpath => "//div[contains(@class, 'wb-snaklistview-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='remove']")
  link(:addReferenceToFirstClaim, :xpath => "//div[contains(@class, 'wb-statement-references-container')][1]/div[contains(@class, 'wb-statement-references')]/span[contains(@class, 'wb-ui-toolbar')]/div/span/span/a")
  link(:editReference1, :xpath => "//div[contains(@class, 'wb-referenceview')][1]/div[contains(@class, 'wb-snaklistview-toolbar')]/span/span/span/span/a[text()='edit']")

  def wait_for_reference_value_box
    wait_until do
      self.referenceValueInput?
    end
  end

  def wait_for_referencesToggle
    wait_until do
      referenceListItems_element.visible?
    end
  end

end
