class ItemPage
  include PageObject
  #def initialize(item_id)
  #  @item_id = item_id
  #end
  # item_id = '10';
  
  # page_url "http://localhost/mediawiki/index.php/Data:q#{@item_id}"
  # page_url "http://localhost/mediawiki/index.php/Data:q" + item_id
  page_url "http://localhost/mediawiki/index.php/Data:q10"
  # page_url "http://localhost/mediawiki/index.php/Data:q" + @item_id
  
  h1(:firstHeading, :id => "firstHeading")
  span(:itemLabelSpan, :xpath => "//h1[@id='firstHeading']/span")
  
  link(:editLabelLink, :css => 
    "h1#firstHeading > 
    div.wb-ui-propertyedittoolbar > 
    div.wb-ui-propertyedittoolbar-group > 
    div.wb-ui-propertyedittoolbar-group > 
    a.wb-ui-propertyedittoolbar-button:nth-child(1)")
  text_field(:labelInputField, :class => "wb-ui-propertyedittoolbar-editablevalueinterface", :index => 0)
  link(:cancelLabelLink, :css => "h1#firstHeading > 
    div.wb-ui-propertyedittoolbar > 
    div.wb-ui-propertyedittoolbar-group > 
    div.wb-ui-propertyedittoolbar-group > 
    a.wb-ui-propertyedittoolbar-button:nth-child(2)")
  link(:saveLabelLinkDisabled, :css => "h1#firstHeading > 
    div.wb-ui-propertyedittoolbar > 
    div.wb-ui-propertyedittoolbar-group > 
    div.wb-ui-propertyedittoolbar-group > 
    span.wb-ui-propertyedittoolbar-button-disabled:nth-child(1)")
  link(:saveLabelLink, :css => "h1#firstHeading > 
    div.wb-ui-propertyedittoolbar > 
    div.wb-ui-propertyedittoolbar-group > 
    div.wb-ui-propertyedittoolbar-group > 
    a.wb-ui-propertyedittoolbar-button:nth-child(1)")
  
end
