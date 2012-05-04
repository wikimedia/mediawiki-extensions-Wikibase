require 'ruby_selenium'

class SitelinksItemPage < ItemPage
  include PageObject
        
  # language links UI
  table(:sitelinksTable, :class => "wb-sitelinks")
  link(:addSitelinkLink, :css => "table.wb-sitelinks > tfoot > tr > td > div.wb-ui-toolbar > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  # element(:siteLinkTableHead, :th, :xpath => "//table[@class='wb-sitelinks']/thead/tr/th")
  span(:siteLinkCounter, :class => "wb-ui-propertyedittool-counter")
  # cell(:siteIdCell, :xpath => "//table[@class='wb-sitelinks']/tbody/tr[1]/td[1]")
end
