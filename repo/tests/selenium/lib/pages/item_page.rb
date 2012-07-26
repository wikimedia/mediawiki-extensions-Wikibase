# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for item page

require 'ruby_selenium'

class ItemPage < RubySelenium
  include PageObject

  # edit label UI
  h1(:firstHeading, :id => "firstHeading")
  div(:uiToolbar, :class => "wb-ui-toolbar")
  span(:itemLabelSpan, :xpath => "//h1[@id='firstHeading']/span[contains(@class, 'wb-ui-labeledittool')]/span/span")
  link(:editLabelLink, :css => "h1#firstHeading > span.wb-ui-labeledittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  link(:editLabelLinkDisabled, :css => "h1#firstHeading > span.wb-ui-labeledittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(1)")
  text_field(:labelInputField, :xpath => "//h1[@id='firstHeading']/span[contains(@class, 'wb-ui-labeledittool')]/span/span[contains(@class, 'wb-ui-propertyedittool-editablevalueinterface')]/input")

  link(:cancelLabelLink, :css => "h1#firstHeading > span.wb-ui-labeledittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(2)")
  link(:saveLabelLinkDisabled, :css => "h1#firstHeading > span.wb-ui-labeledittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(1)")
  link(:cancelLabelLinkDisabled, :css => "h1#firstHeading > span.wb-ui-labeledittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(2)")
  link(:saveLabelLink, :css => "h1#firstHeading > span.wb-ui-labeledittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")

  # edit description UI
  span(:itemDescriptionSpan, :xpath => "//span[@id='wb-description']/span[contains(@class, 'wb-ui-propertyedittool-editablevalue')]/span[contains(@class, 'wb-ui-propertyedittool-editablevalueinterface')]")
  link(:editDescriptionLink, :css => "span.wb-ui-descriptionedittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  link(:editDescriptionLinkDisabled, :css => "span.wb-ui-descriptionedittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(1)")
  text_field(:descriptionInputField, :xpath => "//span[@id='wb-description']/span[contains(@class, 'wb-ui-propertyedittool-editablevalue')]/span[contains(@class, 'wb-ui-propertyedittool-editablevalueinterface')]/input")
  link(:cancelDescriptionLink, :css => "span.wb-ui-descriptionedittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(2)")
  link(:saveDescriptionLinkDisabled, :css => "span.wb-ui-descriptionedittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button:nth-child(1)")
  link(:cancelDescriptionLinkDisabled, :css => "span.wb-ui-descriptionedittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button:nth-child(2)")
  link(:saveDescriptionLink, :css => "span.wb-ui-descriptionedittool > span.wb-ui-propertyedittool-editablevalue > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")

  span(:apiCallWaitingMessage, :class => "wb-ui-propertyedittool-editablevalue-waitmsg")

  # edit-tab
  list_item(:editTab, :id => "ca-edit")

  #tooltip
  div(:wbTooltip, :class => "tipsy-inner")

  # error tooltips
  div(:wbErrorDiv, :class => "wb-tooltip-error-top-message")
  div(:wbErrorDetailsDiv, :class => "wb-tooltip-error-details")
  link(:wbErrorDetailsLink, :class => "wb-tooltip-error-details-link")

  def wait_for_item_to_load
    wait_until do
      uiToolbar_element.visible?
    end
  end

  def wait_for_api_callback
    #TODO: workaround for weird error randomly claiming that apiCallWaitingMessage-element is not attached to the DOM anymore
    sleep 1
    return
    wait_until do
      apiCallWaitingMessage? == false
    end
  end

  def wait_for_editLabelLink
    wait_until do
      editLabelLink?
    end
  end

end
