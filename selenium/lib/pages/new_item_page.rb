# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for new item special page

require 'ruby_selenium'

class NewItemPage < RubySelenium
  include PageObject
  page_url WIKI_REPO_URL + "index.php/Special:CreateItem"

  @@item_url = ""
  @@item_id = ""

  div(:newItemNotification, :id => "wb-specialcreateitem-newitemnotification")
  def create_new_item(label, description)
    wait_for_item_to_load
    self.labelInputField = label
    saveLabelLink
    wait_for_api_callback
    wait_for_new_item_creation
    self.descriptionInputField = description
    saveDescriptionLink
    wait_for_api_callback
    url = current_url
    @@item_url = url[0, url.index('?')]
    @@item_id = @@item_url[@@item_url.index('Data:Q')+6..-1]
    navigate_to_item
    wait_for_item_to_load
  end

  def wait_for_new_item_creation
    wait_until do
      newItemNotification_element.visible?
    end
  end

  def navigate_to_item
    navigate_to @@item_url
  end

  def navigate_to_item_en
    navigate_to @@item_url + "?uselang=en"
  end

  def navigate_to_item_de
    navigate_to @@item_url + "?uselang=de"
  end

  def get_item_id
    @@item_id
  end

end
