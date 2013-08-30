# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for entity page

#require 'ruby_selenium'

module EntityPage
  include PageObject
  include SitelinkPage
  include AliasPage
  include StatementPage
  include ULSPage

  @@property_url = ""
  @@property_id = ""
  @@item_url = ""
  @@item_id = ""

  # ***** ACCESSORS *****
  # label UI
  h1(:mwFirstHeading, :id => "firstHeading")
  h1(:firstHeading, :xpath => "//h1[contains(@class, 'wb-firstHeading')]")
  h1(:uiPropertyEdittool, :class => "wb-ui-propertyedittool")
  span(:entityLabelSpan, :xpath => "//h1[contains(@class, 'wb-firstHeading')]/span/span")
  text_field(:labelInputField, :xpath => "//h1[contains(@class, 'wb-firstHeading')]/span/span/input")
  #link(:editLabelLink,        :css => "h1.wb-firstHeading .wikibase-toolbar > a.wikibase-toolbarbutton:not(.wikibase-toolbarbutton-disabled):nth-child(1)")
  link(:editLabelLink, :css => "h1.wb-firstHeading .wikibase-toolbar > a.wikibase-toolbarbutton-edit:not(.wikibase-toolbarbutton-disabled)")
  link(:editLabelLinkDisabled,    :css => "h1.wb-firstHeading .wikibase-toolbar > a.wikibase-toolbarbutton-disabled:nth-child(1)")
  link(:saveLabelLink, :css => "h1.wb-firstHeading .wikibase-toolbar > a.wikibase-toolbarbutton-save:not(.wikibase-toolbarbutton-disabled)")
  link(:saveLabelLinkDisabled,    :css => "h1.wb-firstHeading .wikibase-toolbar > a.wikibase-toolbarbutton-disabled:nth-child(1)")
  link(:cancelLabelLink, :css => "h1.wb-firstHeading .wikibase-toolbar > a.wikibase-toolbarbutton-cancel:not(.wikibase-toolbarbutton-disabled)")
  link(:cancelLabelLinkDisabled,  :css => "h1.wb-firstHeading .wikibase-toolbar > a.wikibase-toolbarbutton-disabled:nth-child(2)")

  # description UI
  span(:entityDescriptionSpan, :xpath => "//div[contains(@class, 'wb-ui-descriptionedittool')]/span[contains(@class, 'wb-property-container-value')]/span")
  text_field(:descriptionInputField, :xpath => "//div[contains(@class, 'wb-ui-descriptionedittool')]/span[contains(@class, 'wb-property-container-value')]/span/input")
  #link(:editDescriptionLink,      :css => "div.wb-ui-descriptionedittool .wikibase-toolbar > a.wikibase-toolbarbutton:not(.wikibase-toolbarbutton-disabled):nth-child(1)")
  link(:editDescriptionLink, :css => "div.wb-ui-descriptionedittool .wikibase-toolbar > a.wikibase-toolbarbutton-edit:not(.wikibase-toolbarbutton-disabled)")
  link(:editDescriptionLinkDisabled,  :css => "div.wb-ui-descriptionedittool .wikibase-toolbar > a.wikibase-toolbarbutton-disabled:nth-child(1)")
  link(:saveDescriptionLink, :css => "div.wb-ui-descriptionedittool .wikibase-toolbar > a.wikibase-toolbarbutton-save:not(.wikibase-toolbarbutton-disabled)")
  link(:saveDescriptionLinkDisabled,  :css => "div.wb-ui-descriptionedittool .wikibase-toolbar > a.wikibase-toolbarbutton-disabled:nth-child(1)")
  link(:cancelDescriptionLink, :css => "div.wb-ui-descriptionedittool .wikibase-toolbar > a.wikibase-toolbarbutton-cancel:not(.wikibase-toolbarbutton-disabled)")
  link(:cancelDescriptionLinkDisabled,  :css => "div.wb-ui-descriptionedittool .wikibase-toolbar > a.wikibase-toolbarbutton-disabled:nth-child(2)")

  span(:apiCallWaitingMessage, :class => "wb-ui-propertyedittool-editablevalue-waitmsg")

  # edit-tab
  list_item(:editTab, :id => "ca-edit")

  # spinner
  div(:entitySpinner, :xpath => "//div[contains(@class, 'wb-entity-spinner')]")

  # tooltips & error tooltips
  div(:wbTooltip, :class => "tipsy-inner")
  div(:wbErrorDiv, :class => "wikibase-wbtooltip-error-top-message")
  div(:wbErrorDetailsDiv, :class => "wikibase-wbtooltip-error-details")
  link(:wbErrorDetailsLink, :class => "wikibase-wbtooltip-error-details-link")

  # mw notifications
  div(:mwNotificationContent, :xpath => "//div[@id='mw-notification-area']/div/div[contains(@class, 'mw-notification-content')]")

  # ***** METHODS *****
  def navigate_to_entity url
    navigate_to url
    wait_for_entity_to_load
  end

  def wait_for_api_callback
    #TODO: workaround for weird error randomly claiming that apiCallWaitingMessage-element is not attached to the DOM anymore
    wait_until do
      apiCallWaitingMessage? == false
    end
  end

  def wait_for_entity_to_load
    wait_until do
      entitySpinner? == false
    end
  end

  def wait_for_editLabelLink
    wait_until do
      editLabelLink?
    end
  end

  def change_label(label)
    if editLabelLink?
      editLabelLink
    end
    self.labelInputField= label
    saveLabelLink
    ajax_wait
    wait_for_api_callback
  end

  def change_description(description)
    if editDescriptionLink?
      editDescriptionLink
    end
    self.descriptionInputField= description
    saveDescriptionLink
    ajax_wait
    wait_for_api_callback
  end

  def wait_for_mw_notification_shown
    wait_until do
      mwNotificationContent? == true
    end
  end

  def ajax_wait
    sleep 1
    while (script = @browser.execute_script("return jQuery.active")) != 0 do
      sleep(1.0/3)
    end
    return true
  end

  # creates a random string
  def generate_random_string(length=8)
    chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
    string = ''
    length.times { string << chars[rand(chars.size)] }
    return string
  end
end
