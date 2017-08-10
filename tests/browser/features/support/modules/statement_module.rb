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
  include ReferencePage
  include RankSelectorPage
  include SnaktypeSelectorPage

  # statements UI elements
  span(:statements_heading, id: 'claims')
  a(:add_statement, css: 'div.wikibase-statementgrouplistview > .wikibase-addtoolbar-container span.wikibase-toolbar-button-add:not(.wikibase-toolbarbutton-disabled) > a')
  a(:add_statement_disabled, css: 'div.wikibase-statementgrouplistview > .wikibase-addtoolbar-container span.wikibase-toolbar-button-add.wikibase-toolbarbutton-disabled > a')
  a(:edit_statement, css: '.wikibase-statementlistview div.listview-item  .wikibase-edittoolbar-container span.wikibase-toolbar-button-edit:not(.wikibase-toolbarbutton-disabled) > a')
  a(:edit_statement_disabled, css: '.wikibase-statementlistview div.listview-item  .wikibase-edittoolbar-container span.wikibase-toolbar-button-edit.wikibase-toolbarbutton-disabled > a')
  a(:save_statement, css: '.wikibase-statementlistview div.listview-item span.wikibase-toolbar-button-save:not(.wikibase-toolbarbutton-disabled) > a')
  a(:save_statement_disabled, css: '.wikibase-statementlistview div.listview-item span.wikibase-toolbar-button-save.wikibase-toolbarbutton-disabled > a')
  a(:cancel_statement, css: '.wikibase-statementlistview div.listview-item span.wikibase-toolbar-button-cancel:not(.wikibase-toolbarbutton-disabled) > a')
  a(:cancel_statement_disabled, css: '.wikibase-statementlistview div.listview-item span.wikibase-toolbar-button-cancel.wikibase-toolbarbutton-disabled > a')
  span(:statement_help_field, css: 'div.wikibase-statementlistview span.wb-help-field-hint')
  div(:claim_edit_mode, css: '.wb-claim-section div.wb-edit')
  textarea(:claim_value_input_field, css: 'div.wikibase-statementview-mainsnak .valueview-input')

  div(:inputextender_preview, css: 'div.ui-inputextender-extension > div.ui-preview > div.ui-preview-value')
  text_field(:inputextender_input, css: 'div.ui-inputextender-extension > input')
  text_field(:inputextender_unitsuggester, css: 'div.ui-inputextender-extension > .ui-unitsuggester-input')
  a(:time_precision, css: 'div.ui-inputextender-extension div.valueview-expert-TimeInput-precision > a.ui-listrotator-curr')
  a(:time_calendar, css: 'div.ui-inputextender-extension div.valueview-expert-TimeInput-calendar > a.ui-listrotator-curr')
  a(:geo_precision, css: 'div.ui-inputextender-extension div.valueview-expert-GlobeCoordinateInput-precision > a.ui-listrotator-curr')

  ul(:inputextender_dropdown, css: 'ul.ui-suggester-list:not(.ui-entityselector-list):not(.wikibase-siteselector-list)')
  li(:inputextender_dropdown_first, css: 'ul.ui-suggester-list:not(.ui-entityselector-list):not(.wikibase-siteselector-list) li')

  # methods
  def statement_name_element(group_index)
    element('div', css: ".wikibase-statementgroupview:nth-child(#{group_index}) div.wikibase-statementgroupview-property-label")
  end

  def claim_value_string(group_index, claim_index)
    element('div', css: ".wikibase-statementgroupview:nth-child(#{group_index}) div.listview-item:nth-child(#{claim_index}) div.wikibase-snakview-value")
  end

  def claim_value_link(group_index, claim_index)
    element('a', css: ".wikibase-statementgroupview:nth-child(#{group_index}) div.listview-item:nth-child(#{claim_index}) div.wikibase-snakview-value a")
  end

  def claim_snaktype(group_index, claim_index, snaktype)
    element('div', css: ".wikibase-statementgroupview:nth-child(#{group_index}) div.listview-item:nth-child(#{claim_index}) div.wikibase-snakview-value.wikibase-snakview-variation-#{snaktype}snak")
  end

  def snak_value_input_field(index = 1)
    element('textarea', css: "div.wikibase-snaklistview:nth-child(#{index}) .valueview-input")
  end

  def edit_claim_element(group_index, claim_index)
    element('a', css: "div.wikibase-statementgrouplistview div.wikibase-statementgroupview:nth-child(#{group_index}) div.wikibase-statementview:nth-child(#{claim_index}) > .wikibase-edittoolbar-container span.wikibase-toolbar-button-edit:not(.wikibase-toolbarbutton-disabled) > a")
  end

  def add_claim_element(group_index)
    element('a', css: "div.wikibase-statementgrouplistview div.wikibase-statementgroupview:nth-child(#{group_index}) > div > span.wikibase-toolbar-wrapper .wikibase-addtoolbar-container span.wikibase-toolbar-button-add:not(.wikibase-toolbarbutton-disabled) > a")
  end

  def wait_for_claim_value_box
    wait_until do
      claim_value_input_field?
    end
  end

  def wait_for_snak_value_box
    wait_until do
      snak_value_input_field.present?
    end
  end

  def wait_for_statement_request_finished
    wait_until do
      claim_edit_mode? == false
    end
  end

  def wait_for_statement_save_button
    save_statement_element.when_visible
  end

  def get_string_snak_value(value)
    '"' + value + '"'
  end
end
