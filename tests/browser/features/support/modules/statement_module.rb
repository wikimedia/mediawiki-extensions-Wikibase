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
  a(:add_statement, css: "div.wb-claimlistview a.wb-addtoolbar-addbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:add_statement_disabled, css: "div.wb-claimlistview a.wb-addtoolbar-addbutton.wikibase-toolbarbutton-disabled")
  a(:save_statement, css: ".wb-claimlistview div.listview-item a.wikibase-toolbareditgroup-savebutton:not(.wikibase-toolbarbutton-disabled)")
  a(:save_statement_disabled, css: ".wb-claimlistview div.listview-item a.wikibase-toolbareditgroup-savebutton.wikibase-toolbarbutton-disabled")
  a(:cancel_statement, css: ".wb-claimlistview div.listview-item a.wikibase-toolbareditgroup-cancelbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:cancel_statement_disabled, css: ".wb-claimlistview div.listview-item a.wikibase-toolbareditgroup-cancelbutton.wikibase-toolbarbutton-disabled")
  textarea(:statement_value_input, xpath: "//div[contains(@class, 'wb-claimlistview')]//input[contains(@class, 'valueview-input')]")
  textarea(:statement_value, xpath: "//div[contains(@class, 'wb-claimlistview')]//textarea[contains(@class, 'valueview-input')]")
  span(:statement_help_field, :css => "div.wb-claimlistview span.mw-help-field-hint")
  text_field(:statement_value_input_field, class: "valueview-input")
  div(:claim_edit_mode, :css => ".wb-claim-section div.wb-edit")

  #a(:add_claim_to_first_statement, css: "div.wb-claimlistview:nth-child(1) > span.wb-addtoolbar a:not(.wikibase-toolbarbutton-disabled)")
  #a(:edit_first_statement, css: "span.wb-edittoolbar > span > span > span.wikibase-toolbareditgroup-innoneditmode > span > a:not(.wikibase-toolbarbutton-disabled):nth-child(1)")
  #a(:remove_claim_button,	xpath: "//span[contains(@class, 'wb-edittoolbar')]/span/span/span[contains(@class, 'wikibase-toolbareditgroup-ineditmode')]/span/a[not(contains(@class, 'wikibase-toolbarbutton-disabled'))][text()='remove']")
  #div(:claim_edit_mode, xpath: "//div[contains(@class, 'wb-claim-section')]/div[contains(@class, 'wb-edit')]")
  #div(:statement1Name, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claim-name')]")
  #div(:statement2Name, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claim-name')]")
  #a(:statement1Link, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claim-name')]/a")
  #a(:statement2Link, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claim-name')]/a")
  #element(:statement1Claim_value1, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claimview')][1]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement1Claim_value2, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claimview')][2]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement1Claim_value3, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claimview')][3]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement2Claim_value1, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claimview')][1]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement2Claim_value2, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claimview')][2]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #element(:statement2Claim_value3, :a, xpath: "//div[contains(@class, 'wb-claimlistview')][2]//div[contains(@class, 'wb-claimview')][3]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/a")
  #span(:statement1Claim_value1Nolink, xpath: "//div[contains(@class, 'wb-claimlistview')][1]//div[contains(@class, 'wb-claimview')][1]//div[contains(@class, 'wb-claim-mainsnak')]/div[contains(@class, 'wb-snak-value-container')]/div[contains(@class, 'wb-snak-value')]/div/div/span")
  #span(:snaktype_selector_icon, xpath: "//div[contains(@class, 'wb-snak-typeselector')]/span[contains(@class, 'wb-snaktypeselector')]")
  #a(:snaktype_selector_value, xpath: "//ul[contains(@class, 'wb-snaktypeselector-menu')]/li[contains(@class, 'wb-snaktypeselector-menuitem-value')]/a")
  #a(:snaktype_selector_somevalue, xpath: "//ul[contains(@class, 'wb-snaktypeselector-menu')]/li[contains(@class, 'wb-snaktypeselector-menuitem-somevalue')]/a")
  #a(:snaktype_selector_novalue, xpath: "//ul[contains(@class, 'wb-snaktypeselector-menu')]/li[contains(@class, 'wb-snaktypeselector-menuitem-novalue')]/a")
  #span(:preview_spinner, class: "mw-small-spinner")

  def statement_name_element(group_index)
    @browser.element(css: ".wb-claimlistview:nth-child(#{group_index}) div.wb-claim-name")
  end

  def statement_string_value_element(group_index, claim_index)
    @browser.element(xpath: "//div[contains(@class, 'wb-claimlistview')][#{group_index}]//div[contains(@class, 'listview-item')][#{claim_index}]//textarea[contains(@class, 'valueview-input')]")
  end

  def statement_item_value_link(group_index, claim_index)
    @browser.element(xpath: "//div[contains(@class, 'wb-claimlistview')][#{group_index}]//div[contains(@class, 'listview-item')][#{claim_index}]//*[contains(@class, 'valueview-value')]//a")
  end

  def edit_claim(group_index, claim_index)
    edit_claim_element(group_index, claim_index).click
  end

  def edit_claim_element(group_index, claim_index)
    @browser.element(css: ".wb-claimlistview:nth-child(#{group_index}) div.listview-item:nth-child(#{claim_index}) a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  end

  def wait_for_property_value_box
    wait_until do
      self.statement_value_input? || self.statement_value_input_field?
    end
  end

  def wait_for_statement_request_finished
    wait_until do
      self.claim_edit_mode? == false
    end
  end

  def wait_for_save_button
    save_statement_element.when_visible
  end
=begin
  def add_statement(property_label, statement_value)
    add_statement
    self.entity_selector_input = property_label
    ajax_wait
    wait_for_entity_selector_list
    self.wait_for_property_value_box
    if self.statement_value_input?
      self.statement_value_input = statement_value
      ajax_wait
    elsif self.statement_value_input_field?
      self.statement_value_input_field = statement_value
      ajax_wait
    end
    save_statement
    ajax_wait
    self.wait_for_statement_request_finished
  end

  def edit_first_statement(statement_value)
    edit_first_statement
    self.wait_for_property_value_box
    self.statement_value_input_element.clear
    self.statement_value_input = statement_value
    ajax_wait
    save_statement
    ajax_wait
    self.wait_for_statement_request_finished
  end

  def add_claim_to_first_statement(statement_value)
    add_claim_to_first_statement
    self.wait_for_property_value_box
    self.statement_value_input = statement_value
    save_statement
    ajax_wait
    self.wait_for_statement_request_finished
  end

  def remove_all_claims
    while edit_first_statement?
      edit_first_statement
      remove_claim_button
      ajax_wait
      self.wait_for_statement_request_finished
    end
  end

  def change_snaktype(type)
    snaktype_selector_icon_element.click
    if type == "value"
      self.snaktype_selector_value
    elsif type == "somevalue"
      self.snaktype_selector_somevalue
    elsif type == "novalue"
      self.snaktype_selector_novalue
    end
  end
=end
end
