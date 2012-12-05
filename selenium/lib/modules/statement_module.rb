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
  link(:editFirstStatement, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-innoneditmode')]/span/a")
  link(:saveStatement, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[1]")
  link(:cancelStatement, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a[2]")
  #text_area(:newStatementValueInput, :xpath => "//div[contains(@class, 'wb-claim-new')]/div/div/div[contains(@class, 'wb-snak-value')]/div/div/textarea[contains(@class, 'valueview-input')]")
  text_area(:statementValueInput, :xpath => "//div[contains(@class, 'valueview-ineditmode')]/div/a/textarea[contains(@class, 'valueview-input')]")
  #div(:newClaimSection, :xpath => "//div[contains(@class, 'wb-claim-new')]")
  div(:claimEditMode, :xpath => "//div[contains(@class, 'valueview-ineditmode')]")
  div(:firstClaimName, :xpath => "//div[contains(@class, 'wb-claim-section')]/div[contains(@class, 'wb-claim-section-name')]/div[contains(@class, 'wb-claim-name')]")
  element(:firstClaimValue, :a, :xpath => "//div[contains(@class, 'wb-claim-section')]/div[contains(@class, 'wb-claimview')]/div/div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value')]/div/div/a")

  def wait_for_property_value_box
    wait_until do
      self.statementValueInput?
    end
  end

  def wait_for_statement_save_finished
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
    self.statementValueInput = statement_value
    saveStatement
    ajax_wait
    self.wait_for_statement_save_finished
  end
end
