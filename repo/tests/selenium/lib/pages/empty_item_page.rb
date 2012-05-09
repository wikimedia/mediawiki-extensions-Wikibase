require 'ruby_selenium'

class EmptyItemPage < RubySelenium
  include PageObject

  page_url self.get_new_item_url
  # self.set_item_label

  # edit label UI
  h1(:firstHeading, :id => "firstHeading")
  div(:uiToolbar, :class => "wb-ui-toolbar")
  span(:itemLabelSpan, :xpath => "//h1[@id='firstHeading']/span")
  link(:editLabelLink, :css => "h1#firstHeading > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  text_field(:labelInputField, :xpath => "//h1[@id='firstHeading']/span/input")

  link(:cancelLabelLink, :css => "h1#firstHeading > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(2)")
  link(:saveLabelLinkDisabled, :css => "h1#firstHeading > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(1)")
  link(:cancelLabelLinkDisabled, :css => "h1#firstHeading > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(2)")
  link(:saveLabelLink, :css => "h1#firstHeading > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")

  # edit description UI
  span(:itemDescriptionSpan, :class => "wb-property-container-value")
  link(:editDescriptionLink, :css => "div.wb-ui-descriptionedittool > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  text_field(:descriptionInputField, :xpath => "//div[@class='wb-property-container wb-ui-propertyedittool-subject wb-ui-descriptionedittool']/span/input")
  link(:cancelDescriptionLink, :css => "div.wb-ui-descriptionedittool > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(2)")
  link(:saveDescriptionLinkDisabled, :css => "div.wb-ui-descriptionedittool > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button:nth-child(1)")
  link(:cancelDescriptionLinkDisabled, :css => "div.wb-ui-descriptionedittool > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button:nth-child(2)")
  link(:saveDescriptionLink, :css => "div.wb-ui-descriptionedittool > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")

  def wait_for_item_to_load
    wait_until do
      uiToolbar_element.visible?
    end
  end
end
