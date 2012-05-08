require 'ruby_selenium'

class ItemPage < EmptyItemPage
  include PageObject

  page_url self.get_new_item_url
  self.set_item_label
  self.set_item_description

  div(:uiToolbar, :class => "wb-ui-toolbar")
  
  def wait_for_page_to_load
    wait_until do
      uiToolbar_element.visible?
    end
  end
end
