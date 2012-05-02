class ItemPage
  include PageObject

  # page_url "http://localhost/mediawiki/index.php/Data:q#{@item_id}"
  # page_url "http://localhost/mediawiki/index.php/Data:q" + item_id
  # page_url "http://localhost/mediawiki/index.php/Data:q10?uselang=en"
  page_url "http://208.80.153.239/w/index.php/Data:Q500?uselang=en"
  # page_url "http://localhost/mediawiki/index.php/Data:q" + @item_id

  # page title
  expected_title("mediawiki")
  
  # edit label UI
  h1(:firstHeading, :id => "firstHeading")
  span(:itemLabelSpan, :xpath => "//h1[@id='firstHeading']/span")
  link(:editLabelLink, :css => "h1#firstHeading > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  text_field(:labelInputField, :class => "wb-ui-propertyedittool-editablevalueinterface", :index => 0)
  link(:cancelLabelLink, :css => "h1#firstHeading > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(2)")
  link(:saveLabelLinkDisabled, :css => "h1#firstHeading > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(1)")
  link(:saveLabelLink, :css => "h1#firstHeading > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")

  # edit description UI
  span(:itemDescriptionSpan, :class => "wb-property-container-value")
  link(:editDescriptionLink, :css => "div.wb-ui-descriptionedittool > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  text_field(:descriptionInputField, :class => "wb-ui-propertyedittool-editablevalueinterface")
  link(:cancelDescriptionLink, :css => "div.wb-ui-descriptionedittool > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(2)")
  link(:saveDescriptionLinkDisabled, :css => "div.wb-ui-descriptionedittool > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button:nth-child(1)")
  link(:saveDescriptionLink, :css => "div.wb-ui-descriptionedittool > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
      
  # language links UI
  table(:sitelinksTable, :class => "wb-sitelinks")
  link(:addSitelinkLink, :css => "table.wb-sitelinks > tfoot > tr > td > div.wb-ui-toolbar > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  cell(:siteIdCell, :xpath => "//table[@class='wb-sitelinks']/tbody/tr[1]/td[1]")
end
