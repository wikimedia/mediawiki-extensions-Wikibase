require 'ruby_selenium'

class NewItemPage < RubySelenium
  include PageObject
  page_url WIKI_URL + "index.php/Special:CreateItem"

  div(:uiToolbar, :class => "wb-ui-toolbar")
  text_field(:labelInputField, :xpath => "//h1[@id='firstHeading']/span/span/input")
  link(:saveLabelLink, :css => "h1#firstHeading > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  text_field(:descriptionInputField, :xpath => "//div[@id='mw-content-text']/div/span/span/input")
  link(:saveDescriptionLink, :css => "div.wb-ui-descriptionedittool > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  span(:apiCallWaitingMessage, :class => "wb-ui-propertyedittool-editablevalue-waitmsg")
  div(:newItemNotification, :id => "wb-specialcreateitem-newitemnotification")
  span(:itemLabelSpan, :xpath => "//h1[@id='firstHeading']/span/span")
  span(:itemDescriptionSpan, :xpath => "//div[@id='mw-content-text']/div/span/span")
  
  def create_new_item(label, description)
    self.labelInputField = label
    saveLabelLink
    wait_for_api_callback
    wait_for_new_item_creation
    self.descriptionInputField = description
    saveDescriptionLink
    wait_for_api_callback
  end

  def wait_for_item_to_load
    wait_until do
      uiToolbar_element.visible?
    end
  end

  def wait_for_new_item_creation
    wait_until do
      newItemNotification_element.visible?
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
end
