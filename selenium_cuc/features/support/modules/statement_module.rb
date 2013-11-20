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
  #include ReferencePage
  #include QualifierPage

  # statements UI elements
  a(:addStatement, css: "div.wb-claimlistview a.wb-addtoolbar-addbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:addStatementDisabled, css: "div.wb-claimlistview a.wb-addtoolbar-addbutton.wikibase-toolbarbutton-disabled")
  a(:saveStatement, css: ".wb-claimlistview div.listview-item.wb-new a.wikibase-toolbareditgroup-savebutton:not(.wikibase-toolbarbutton-disabled)")
  a(:saveStatementDisabled, css: ".wb-claimlistview div.listview-item.wb-new a.wikibase-toolbareditgroup-savebutton.wikibase-toolbarbutton-disabled")
  a(:cancelStatement, css: ".wb-claimlistview div.listview-item.wb-new a.wikibase-toolbareditgroup-cancelbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:cancelStatementDisabled, css: ".wb-claimlistview div.listview-item.wb-new a.wikibase-toolbareditgroup-cancelbutton.wikibase-toolbarbutton-disabled")
  textarea(:statementValueInput, xpath: "//div[contains(@class, 'wb-claimlistview')]//input[contains(@class, 'valueview-input')]")
  span(:statementHelpField, :css => "div.wb-claimlistview span.mw-help-field-hint")
  text_field(:statementValueInputField, class: "valueview-input")

  #a(:addClaimToFirstStatement, css: "div.wb-claimlistview:nth-child(1) > span.wb-addtoolbar a:not(.wikibase-toolbarbutton-disabled)")
  #a(:editFirstStatement, css: "span.wb-edittoolbar > span > span > span.wikibase-toolbareditgroup-innoneditmode > span > a:not(.wikibase-toolbarbutton-disabled):nth-child(1)")
  #a(:removeClaimButton,	xpath: "//span[contains(@class, 'wb-edittoolbar')]/span/span/span[contains(@class, 'wikibase-toolbareditgroup-ineditmode')]/span/a[not(contains(@class, 'wikibase-toolbarbutton-disabled'))][text()='remove']")

  #div(:claimEditMode, xpath: "//div[contains(@class, 'wb-claim-section')]/div[contains(@class, 'wb-edit')]")
  #div(:statement1Name, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claim-name')]")
  #div(:statement2Name, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claim-name')]")
  #a(:statement1Link, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claim-name')]/a")
  #a(:statement2Link, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claim-name')]/a")
  #element(:statement1ClaimValue1, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claimview')][1]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement1ClaimValue2, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claimview')][2]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement1ClaimValue3, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claimview')][3]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement2ClaimValue1, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claimview')][1]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement2ClaimValue2, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claimview')][2]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement2ClaimValue3, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claimview')][3]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #span(:statement1ClaimValue1Nolink, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claimview')][1]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/span")
  #span(:snaktypeSelectorIcon, xpath: "//div[contains(@class, 'wb-snak-typeselector')]/span[contains(@class, 'wb-snaktypeselector')]")
  #a(:snaktypeSelectorValue, xpath: "//ul[contains(@class, 'wb-snaktypeselector-menu')]/li[contains(@class, 'wb-snaktypeselector-menuitem-value')]/a")
  #a(:snaktypeSelectorSomevalue, xpath: "//ul[contains(@class, 'wb-snaktypeselector-menu')]/li[contains(@class, 'wb-snaktypeselector-menuitem-somevalue')]/a")
  #a(:snaktypeSelectorNovalue, xpath: "//ul[contains(@class, 'wb-snaktypeselector-menu')]/li[contains(@class, 'wb-snaktypeselector-menuitem-novalue')]/a")
  #span(:previewSpinner, class: "mw-small-spinner")

  def wait_for_property_value_box
    wait_until do
      self.statementValueInput? || self.statementValueInputField?
    end
  end

=begin
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
      ajax_wait
    elsif self.statementValueInputField?
      self.statementValueInputField = statement_value
      ajax_wait
    end
    saveStatement
    ajax_wait
    self.wait_for_statement_request_finished
  end

  def edit_first_statement(statement_value)
    editFirstStatement
    self.wait_for_property_value_box
    self.statementValueInput_element.clear
    self.statementValueInput = statement_value
    ajax_wait
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

  def change_snaktype(type)
    snaktypeSelectorIcon_element.click
    if type == "value"
      self.snaktypeSelectorValue
    elsif type == "somevalue"
      self.snaktypeSelectorSomevalue
    elsif type == "novalue"
      self.snaktypeSelectorNovalue
    end
  end
=end
end
