# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for statement page

module StatementPage
  include PageObject
  # statements UI elements
  link(:addStatement, :xpath => "//div[contains(@class, 'wb-claims-toolbar')]/div/span/span/a")
  link(:editFirstStatement, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-innoneditmode')]/span/a")
  link(:saveFirstStatement, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a")
  link(:cancelFirstStatement, :xpath => "//div[contains(@class, 'wb-claim-toolbar')]/span/span/span[contains(@class, 'wb-ui-toolbar-editgroup-ineditmode')]/span/a")
  text_field(:newStatementPropertyInput, :xpath => "//div[contains(@class, 'wb-claim-new')]/div/div/div[contains(@class, 'wb-snak-property')]/input[contains(@class, 'ui-entityselector-input')]")
  text_area(:newStatementValueInput, :xpath => "//div[contains(@class, 'wb-claim-new')]/div/div/div[contains(@class, 'wb-snak-value')]/textarea[contains(@class, 'valueview-input')]")
end
