class ItemPage
  include PageObject
  #def initialize(item_id)
  #  @item_id = item_id
  #end
  item_id = '10';
  
  #page_url "http://localhost/mediawiki/index.php/Data:q#{@item_id}"
  #page_url "http://localhost/mediawiki/index.php/Data:q" + item_id
  page_url "http://localhost/mediawiki/index.php/Data:q10"
  
  h1(:firstHeading, :id => "firstHeading")
  span(:itemLabelSpan, :xpath => "//h1[@id='firstHeading']/span")
  # span(:itemLabelSpan, :css => "h1#firstHeading > span")
  # link(:editLink, :link_text => "edit", :index => 0)
  link(:editLink, :css => 
    "h1#firstHeading > 
    div.wb-ui-propertyedittoolbar > 
    div.wb-ui-propertyedittoolbar-group > 
    div.wb-ui-propertyedittoolbar-group > 
    a.wb-ui-propertyedittoolbar-button:nth-child(1)")
  text_field(:valueInputField, :class => "wb-ui-propertyedittoolbar-editablevalueinterface", :index => 0)
  # link(:cancelLink, :class => "wb-ui-propertyedittoolbar-button", :link_text => "cancel", :index => 0)
  link(:cancelLink, :css => "h1#firstHeading > 
    div.wb-ui-propertyedittoolbar > 
    div.wb-ui-propertyedittoolbar-group > 
    div.wb-ui-propertyedittoolbar-group > 
    a.wb-ui-propertyedittoolbar-button:nth-child(2)")
  link(:saveLinkDisabled, :css => "h1#firstHeading > 
    div.wb-ui-propertyedittoolbar > 
    div.wb-ui-propertyedittoolbar-group > 
    div.wb-ui-propertyedittoolbar-group > 
    span.wb-ui-propertyedittoolbar-button-disabled:nth-child(1)")
  link(:saveLink, :css => "h1#firstHeading > 
    div.wb-ui-propertyedittoolbar > 
    div.wb-ui-propertyedittoolbar-group > 
    div.wb-ui-propertyedittoolbar-group > 
    a.wb-ui-propertyedittoolbar-button:nth-child(1)")
  
end
