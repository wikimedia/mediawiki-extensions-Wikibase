# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for entity page

# require "ruby_selenium"

module EntityPage
  include PageObject
  include SitelinkPage
  include AliasPage
  include StatementPage
  include ULSPage
  include AuthorityControlGadgetPage

  # ***** ACCESSORS *****
  # label UI
  h1(:first_heading, class: 'firstHeading')
  span(:entity_id_span, css: '.wikibase-title-id')
  span(:entity_label_span, css: '.wikibase-title-label')
  text_area(:label_input_field, css: '.wikibase-labelview-text textarea')
  a(:edit_header_link, css: '.wikibase-entitytermsview span.wikibase-toolbar-button-edit:not(.wikibase-toolbarbutton-disabled) > a')
  a(:edit_header_link_disabled, css: '.wikibase-entitytermsview  span.wikibase-toolbar-button-edit.wikibase-toolbarbutton-disabled > a')
  a(:save_header_link, css: '.wikibase-entitytermsview  span.wikibase-toolbar-button-save:not(.wikibase-toolbarbutton-disabled) > a')
  a(:save_header_link_disabled, css: '.wikibase-entitytermsview  span.wikibase-toolbar-button-save.wikibase-toolbarbutton-disabled > a')
  a(:cancel_header_link, css: '.wikibase-entitytermsview  span.wikibase-toolbar-button-cancel:not(.wikibase-toolbarbutton-disabled) > a')
  a(:cancel_header_link_disabled, css: '.wikibase-entitytermsview  span.wikibase-toolbar-button-cancel.wikibase-toolbarbutton-disabled > a')

  # description UI
  div(:entity_description_div, class: 'wikibase-entitytermsview-heading-description')
  text_area(:description_input_field, css: '.wikibase-descriptionview-text textarea')

  span(:terms_view_toggler, css: '.wikibase-entitytermsview-entitytermsforlanguagelistview-toggler span.ui-toggler-label')
  span(:en_terms_view_label, css: '.wikibase-entitytermsforlanguageview-en span.wikibase-labelview-text')

  span(:api_call_waiting_message, class: 'wb-actionmsg')

  # edit-tab
  li(:edit_tab, id: 'ca-edit')

  # spinner
  div(:entity_spinner, xpath: "//div[contains(@class, 'wb-entity-spinner')]")

  # tooltips & error tooltips
  div(:wb_tooltip, class: 'tipsy-inner')
  div(:wb_error_div, class: 'wikibase-wbtooltip-error')
  div(:wb_error_details_div, class: 'wikibase-wbtooltip-error-details')
  a(:wb_error_details_link, class: 'wikibase-wbtooltip-error-details-link')

  # mw notifications
  div(:mw_notification_content, xpath: "//div[@id='mw-notification-area']/div/div[contains(@class, 'mw-notification-content')]")

  # ***** METHODS *****
  def navigate_to_entity(url)
    navigate_to url
    wait_for_entity_to_load
  end

  def create_item_data_from_page
    id = entity_id_span_element.text.gsub(/\(|\)/, '')
    url = URL.repo_url(ENV['ITEM_NAMESPACE'] + id)
    label_en = first_heading_element.text.gsub(/(.*)(\(#{id}\))(.*)/, '\1\3')
    description_en = entity_description_div_element.text

    { 'id' => id, 'url' => url, 'label' => label_en, 'description' => description_en }
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

  def wait_for_label(label)
    wait_until do
      browser.title.include?(label)
    end
  end

  def change_label(label)
    if edit_header_link?
      edit_header_link
    end
    self.label_input_field = label
    save_header_link
    ajax_wait
    wait_for_api_callback
  end

  def change_description(description)
    if edit_description_link?
      edit_description_link
    end
    self.description_input_field = description
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
    Timeout.timeout(5) do
      sleep(1.0 / 3) while execute_script('return jQuery.active') != 0
    end
    sleep 1
    true
  end

  def wait_until_jquery_animation_finished
    wait_until do
      execute_script('return jQuery(":animated").length') == 0
    end
  end

  # creates a random string
  def generate_random_string(length = 8)
    chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
    string = ''
    length.times { string << chars[rand(chars.size)] }
    string
  end

  def set_copyright_ack_cookie
    cookie = "$.cookie( 'wikibase.acknowledgedcopyrightversion', 'wikibase-1', { 'expires': null, 'path': '/' } );"
    execute_script(cookie)
  end

  def set_noanonymouseditwarning_cookie
    cookie = "$.cookie( 'wikibase-no-anonymouseditwarning', '1', { 'expires': null, 'path': '/' } );"
    execute_script(cookie)
  end

  # this method was moved from wikibase_api_module.rb since we are now using the mediawiki_api/wikidata gem for doing API requests
  # this method is really ugly and should be refactored
  def create_entity_and_properties(serialization)
    wb_api = MediawikiApi::Wikidata::WikidataClient.new URL.repo_api
    wb_api.log_in(ENV['WB_REPO_USERNAME'], ENV['WB_REPO_PASSWORD'])

    serialization['properties'].each do |old_id, prop|
      if prop['description'] && prop['description']['en']['value']
        search = prop['description']['en']['value']
      else
        search = prop['labels']['en']['value']
      end
      resp = wb_api.search_entities(search, 'en', 'property')
      resp['search'].reject! do |found_prop|
        found_prop['label'] != prop['labels']['en']['value']
      end
      if resp['search'][0]
        id = resp['search'][0]['id']
      else
        saved_prop = wb_api.create_property(prop)
        id = saved_prop['id']
      end

      serialization['entity']['claims'].each do |claim|
        if claim['mainsnak']['property'] == old_id
          claim['mainsnak']['property'] = id
        end
        if claim['qualifiers']
          claim['qualifiers'].each do |qualifier|
            if qualifier['property'] == old_id
              qualifier['property'] = id
            end
          end
        end
        if claim['qualifiers-order']
          claim['qualifiers-order'].map! do |p_id|
            p_id == old_id ? id : p_id
          end
        end
        if claim['references']
          claim['references'].each do |reference|
            reference['snaks'].each do |snak|
              if snak['property'] == old_id
                snak['property'] = id
              end
            end
            reference['snaks-order'].map! do |p_id|
              p_id == old_id ? id : p_id
            end
          end
        end
      end
    end

    wb_api.create_item(serialization['entity'])
  end
end
