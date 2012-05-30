require 'ruby_selenium'

class NewItemPage < ItemPage
  include PageObject
  page_url WIKI_URL + "index.php/Special:CreateItem"

  @@item_url = ""

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

end
