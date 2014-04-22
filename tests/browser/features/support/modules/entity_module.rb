# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for entity page

#require "ruby_selenium"

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
  h1(:mw_first_heading, id: "firstHeading")
  h1(:first_heading, xpath: "//h1[contains(@class, 'wb-firstHeading')]")
  h1(:ui_property_edittool, class: "wb-ui-propertyedittool")
  span(:entity_label_span, xpath: "//h1[contains(@class, 'wb-firstHeading')]/span/span")
  span(:entity_id_span, xpath: "//h1[contains(@class, 'wb-firstHeading')]/span/span[contains(@class, 'wb-value-supplement')]")
  text_field(:label_input_field, xpath: "//h1[contains(@class, 'wb-firstHeading')]/span/span/input")
  a(:edit_label_link, css: "h1.wb-firstHeading a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:edit_label_link_disabled, css: "h1.wb-firstHeading a.wikibase-toolbareditgroup-editbutton.wikibase-toolbarbutton-disabled")
  a(:save_label_link, css: "h1.wb-firstHeading a.wikibase-toolbareditgroup-savebutton:not(.wikibase-toolbarbutton-disabled)")
  a(:save_label_link_disabled, css: "h1.wb-firstHeading a.wikibase-toolbareditgroup-savebutton.wikibase-toolbarbutton-disabled")
  a(:cancel_label_link, css: "h1.wb-firstHeading a.wikibase-toolbareditgroup-cancelbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:cancel_label_link_disabled, css: "h1.wb-firstHeading a.wikibase-toolbareditgroup-cancelbutton.wikibase-toolbarbutton-disabled")

  # description UI
  span(:entity_description_span, xpath: "//div[contains(@class, 'wb-ui-descriptionedittool')]/span[contains(@class, 'wb-property-container-value')]/span")
  text_field(:description_input_field, xpath: "//div[contains(@class, 'wb-ui-descriptionedittool')]/span[contains(@class, 'wb-property-container-value')]/span/input")
  a(:edit_description_link, css: "div.wb-ui-descriptionedittool a.wikibase-toolbareditgroup-editbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:edit_description_link_disabled, css: "div.wb-ui-descriptionedittool a.wikibase-toolbareditgroup-editbutton.wikibase-toolbarbutton-disabled")
  a(:save_description_link, css: "div.wb-ui-descriptionedittool a.wikibase-toolbareditgroup-savebutton:not(.wikibase-toolbarbutton-disabled)")
  a(:save_description_link_disabled, css: "div.wb-ui-descriptionedittool a.wikibase-toolbareditgroup-savebutton.wikibase-toolbarbutton-disabled")
  a(:cancel_description_link, css: "div.wb-ui-descriptionedittool a.wikibase-toolbareditgroup-cancelbutton:not(.wikibase-toolbarbutton-disabled)")
  a(:cancel_description_link_disabled, css: "div.wb-ui-descriptionedittool a.wikibase-toolbareditgroup-cancelbutton.wikibase-toolbarbutton-disabled")

  span(:api_call_waiting_message, class: "wb-ui-propertyedittool-editablevalue-waitmsg")

  # edit-tab
  li(:edit_tab, id: "ca-edit")

  # spinner
  div(:entity_spinner, xpath: "//div[contains(@class, 'wb-entity-spinner')]")

  # tooltips & error tooltips
  div(:wb_tooltip, class: "tipsy-inner")
  div(:wb_error_div, class: "wikibase-wbtooltip-error-top-message")
  div(:wb_error_details_div, class: "wikibase-wbtooltip-error-details")
  a(:wb_error_details_link, class: "wikibase-wbtooltip-error-details-link")

  # mw notifications
  div(:mw_notification_content, xpath: "//div[@id='mw-notification-area']/div/div[contains(@class, 'mw-notification-content')]")

  # ***** METHODS *****
  def navigate_to_entity url
    navigate_to url
    wait_for_entity_to_load
  end

  def wait_for_api_callback
    ajax_wait
    api_call_waiting_message_element.when_not_visible
  end

  def wait_for_entity_to_load
    wait_until do
      entity_spinner? == false
    end
  end

  def wait_for_edit_label_link
    wait_until do
      edit_label_link?
    end
  end

  def change_label(label)
    if edit_label_link?
      edit_label_link
    end
    self.label_input_field= label
    save_label_link
    ajax_wait
    wait_for_api_callback
  end

  def change_description(description)
    if edit_description_link?
      edit_description_link
    end
    self.description_input_field= description
    save_description_link
    ajax_wait
    wait_for_api_callback
  end

  def wait_for_mw_notification_shown
    wait_until do
      mw_notification_content? == true
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
    chars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ"
    string = ""
    length.times { string << chars[rand(chars.size)] }
    return string
  end

  def set_copyright_ack_cookie
    cookie = "$.cookie( 'wikibase.acknowledgedcopyrightversion', 'wikibase-1', { 'expires': null, 'path': '/' } );"
    @browser.execute_script(cookie)
  end

  def set_noanonymouseditwarning_cookie
    cookie = "$.cookie( 'wikibase-no-anonymouseditwarning', '1', { 'expires': null, 'path': '/' } );"
    @browser.execute_script(cookie)
  end

end
