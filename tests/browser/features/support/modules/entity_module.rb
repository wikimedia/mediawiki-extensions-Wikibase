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
  div(:mw_notification_content, xpath: "//div[contains(@class, 'mw-notification-area')]/div/div[contains(@class, 'mw-notification-content')]")

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
    sleep_period = 0.25
    timeout_seconds = 2
    timeout_loops = (timeout_seconds / sleep_period).to_i

    while execute_script('return jQuery.active') != 0 && timeout_loops > 0
      sleep(sleep_period)
      timeout_loops -= 1
    end
    sleep 1
    true
  end

  def wait_until_jquery_animation_finished
    wait_until do
      execute_script('return jQuery(":animated").length') == 0
    end
  end

  def wait_for_search_index_update(page_titles)
    return if ENV['USES_CIRRUS_SEARCH'] != 'true'

    mw_api = MediawikiApi::Client.new URL.repo_api

    sleep_period = 15
    timeout_seconds = 300
    timeout_loops = (timeout_seconds / sleep_period).to_i

    while timeout_loops > 0
      if page_index_updated(mw_api, page_titles)
        break
      end

      sleep(sleep_period)
      timeout_loops -= 1
    end
  end

  def page_index_updated(api, page_titles)
    response = api.action(:query, prop: 'cirrusdoc|revisions', titles: page_titles.join('|'), rvprop: 'ids', token_type: false).data
    if response.nil? || !response.key?('query') || !response['query'].key?('pages')
      return false
    end
    response['query']['pages'].each_value do |page_data|
      if page_data.nil? || !page_data.key?('cirrusdoc') || !page_data.key?('revisions')
        return false
      end
      revision_id = page_data['revisions'][0]['revid']
      if page_data['cirrusdoc'][0]['source']['version'] != revision_id
        return false
      end
    end
    true
  end

  # creates a random string
  def generate_random_string(length = 8)
    chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ'
    string = ''
    length.times { string << chars[rand(chars.size)] }
    string
  end

  def set_copyright_ack_cookie
    wait_until_cookie_loaded
    cookie = "mw.cookie.set( 'wikibase.acknowledgedcopyrightversion', 'wikibase-1', { 'expires': null, 'path': '/' } );"
    execute_script(cookie)
  end

  def set_noanonymouseditwarning_cookie
    wait_until_cookie_loaded
    cookie = "mw.cookie.set( 'wikibase-no-anonymouseditwarning', '1', { 'expires': null, 'path': '/' } );"
    execute_script(cookie)
  end

  def wait_until_cookie_loaded
    wait_until do
      execute_script(
        'return (
          typeof window.mw.loader === \'object\' &&
          typeof window.mw.loader.getState === \'function\' &&
          window.mw.loader.getState( \'mediawiki.cookie\' ) === \'ready\'
        )'
      ) != false
    end
  end
end
