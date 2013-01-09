# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for statement page

module StatementPage
  include PageObject
  include EntitySelectorPage
  # statements UI elements
  link(:addStatement, :xpath => "//div[contains(@class, 'wb-claims-toolbar')]/div/span/span/a")
  link(:addClaimToFirstStatement, :xpath => "//div[contains(@class, 'wb-claim-section')][1]/div[contains(@class, 'wb-claim-add')]/div[contains(@class, 'wb-claim-toolbar')]/span/span/span/a")
  link(:editFirstStatement, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-innoneditmode')]/span/a")
  link(:saveStatement, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='save']")
  link(:cancelStatement, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='cancel']")
  link(:removeClaimButton, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[text()='remove']")
  text_area(:statementValueInput, :xpath => "//div[contains(@class, 'valueview-ineditmode')]/div/a/textarea[contains(@class, 'valueview-input')]")
  text_field(:statementValueItem, :xpath => "//div[contains(@class, 'valueview-ineditmode')]/div/a/input[contains(@class, 'valueview-input')]")
  div(:claimEditMode, :xpath => "//div[contains(@class, 'valueview-ineditmode')]")
  div(:statement1Name, :xpath => "//div[contains(@class, 'wb-claim-section')][1]/div[contains(@class, 'wb-claim-section-name')]/div[contains(@class, 'wb-claim-name')]")
  div(:statement2Name, :xpath => "//div[contains(@class, 'wb-claim-section')][2]/div[contains(@class, 'wb-claim-section-name')]/div[contains(@class, 'wb-claim-name')]")
  link(:statement1Link, :xpath => "//div[contains(@class, 'wb-claim-section')][1]/div[contains(@class, 'wb-claim-section-name')]/div[contains(@class, 'wb-claim-name')]/a")
  link(:statement2Link, :xpath => "//div[contains(@class, 'wb-claim-section')][2]/div[contains(@class, 'wb-claim-section-name')]/div[contains(@class, 'wb-claim-name')]/a")
  element(:statement1ClaimValue1, :a, :xpath => "//div[contains(@class, 'wb-claim-section')][1]/div[contains(@class, 'wb-claimview')][1]/div/div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  element(:statement1ClaimValue2, :a, :xpath => "//div[contains(@class, 'wb-claim-section')][1]/div[contains(@class, 'wb-claimview')][2]/div/div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  element(:statement1ClaimValue3, :a, :xpath => "//div[contains(@class, 'wb-claim-section')][1]/div[contains(@class, 'wb-claimview')][3]/div/div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  element(:statement2ClaimValue1, :a, :xpath => "//div[contains(@class, 'wb-claim-section')][2]/div[contains(@class, 'wb-claimview')][1]/div/div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  element(:statement2ClaimValue2, :a, :xpath => "//div[contains(@class, 'wb-claim-section')][2]/div[contains(@class, 'wb-claimview')][2]/div/div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  element(:statement2ClaimValue3, :a, :xpath => "//div[contains(@class, 'wb-claim-section')][2]/div[contains(@class, 'wb-claimview')][3]/div/div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")

  def wait_for_property_value_box
    wait_until do
      self.statementValueInput
    end
  end

  def wait_for_statement_request_finished
    wait_until do
      self.claimEditMode? == false
    end
  end

  def add_statement(property_label, statement_value)
    addStatement
    self.entitySelectorInput = property_label
    ajax_wait
    wait_for_entity_selector_list
    self.wait_for_property_value_box
    if self.statementValueInput?
      self.statementValueInput = statement_value
      # TODO: bug 43609: Regarding item property, as long as no verification of the input is done, we have to wait for the entityselector to finish the selection
      ajax_wait
    end
    saveStatement
    ajax_wait
    self.wait_for_statement_request_finished
  end

  def add_claim_to_first_statement(statement_value)
    addClaimToFirstStatement
    self.wait_for_property_value_box
    self.statementValueInput = statement_value
    saveStatement
    ajax_wait
    self.wait_for_statement_request_finished
  end

  def remove_all_claims
    while editFirstStatement?
      editFirstStatement
      removeClaimButton
      ajax_wait
      self.wait_for_statement_request_finished
    end
  end
end
