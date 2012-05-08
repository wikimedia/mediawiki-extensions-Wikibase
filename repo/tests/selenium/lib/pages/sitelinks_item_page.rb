require 'ruby_selenium'

class SitelinksItemPage < ItemPage
  include PageObject

  # language links UI
  table(:sitelinksTable, :class => "wb-sitelinks")
  # tbody(:sitelinksThead, :xpath => "//table[@class='wb-sitelinks wb-ui-propertyedittool-subject']/tbody")
  link(:addSitelinkLink, :css => "table.wb-sitelinks > tfoot > tr > td > div.wb-ui-toolbar > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  # element(:siteLinkTableHead, :th, :xpath => "//table[@class='wb-sitelinks']/thead/tr/th")
  span(:siteLinkCounter, :class => "wb-ui-propertyedittool-counter")
  text_field(:siteIdInputField, :xpath => "//table[@class='wb-sitelinks wb-ui-propertyedittool-subject']/tbody/tr/td[1]/input")
  text_field(:pageInputField, :xpath => "//table[@class='wb-sitelinks wb-ui-propertyedittool-subject']/tbody/tr/td[2]/input")
  text_field(:siteIdInputFieldLoading, :xpath => "//table[@class='wb-sitelinks wb-ui-propertyedittool-subject']/tbody/tr/td[1]/input[@class='ui-autocomplete-loading']")
  text_field(:pageInputFieldLoading, :xpath => "//table[@class='wb-sitelinks wb-ui-propertyedittool-subject']/tbody/tr/td[2]/input[@class='ui-autocomplete-loading']")
  span(:saveSitelinkLinkDisabled, :class => "wb-ui-toolbar-button-disabled")
  unordered_list(:siteIdAutocompleteList, :class => "ui-autocomplete", :index => 0)
  unordered_list(:pageAutocompleteList, :class => "ui-autocomplete", :index => 1)
  unordered_list(:editSitelinkAutocompleteList, :class => "ui-autocomplete", :index => 0)
  # link(:saveSitelinkLink, :css => "table.wb-sitelinks > tbody > tr > td:nth-child(3) > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  # link(:cancelSitelinkLink, :css => "table.wb-sitelinks > tbody > tr > td:nth-child(3) > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(2)")
  link(:saveSitelinkLink, :text => "save")
  link(:cancelSitelinkLink, :text => "cancel")
  link(:removeSitelinkLink, :text => "remove")
  # unordered_list(:siteIdAutocompleteList, :xpath => "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all'][1]")
  # unordered_list(:pageAutocompleteList, :xpath => "//ul[@class='ui-autocomplete ui-menu ui-widget ui-widget-content ui-corner-all'][2]")
  # cell(:siteIdCell, :xpath => "//table[@class='wb-sitelinks']/tbody/tr[1]/td[1]")
  link(:editSitelinkLink, :text => "edit", :index => 3)
  def getNumberOfSitelinksFromCounter
    scanned = siteLinkCounter.scan(/\(([^)]+)\)/)
    integerValue = scanned[0][0].to_i()
    return integerValue
  end

  def countAutocompleteListElements(list)
    count = 0
    list.each do |listItem|
      count = count+1
      # puts count
    end
    return count
  end

  def getNthElementInAutocompleteList(list, n)
    count = 0
    list.each do |listItem|
      count = count+1
      if count == n
        return listItem
      end
    end
    return false
  end

  def countExistingSitelinks
    count = 0
    sitelinksTable_element.each do |tableRow|
      count = count+1
      # puts count
    end
    return count-2
  end

  def wait_until_page_loaded
    wait_until do
      sitelinksTable?
    end
  end

end
