require 'ruby_selenium'

class SitelinksItemPage < ItemPage
  include PageObject

  # language links UI
  table(:sitelinksTable, :class => "wb-sitelinks")
  link(:addSitelinkLink, :css => "table.wb-sitelinks > tfoot > tr > td > div.wb-ui-toolbar > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  span(:siteLinkCounter, :class => "wb-ui-propertyedittool-counter")
  text_field(:siteIdInputField, :xpath => "//div[@id='mw-content-text']/table/tbody/tr/td[1]/input")
  text_field(:pageInputField, :xpath => "//div[@id='mw-content-text']/table/tbody/tr/td[2]/input")
  text_field(:siteIdInputFieldLoading, :xpath => "//div[@id='mw-content-text']/table/tbody/tr/td[1]/input[@class='ui-autocomplete-loading']")
  text_field(:pageInputFieldLoading, :xpath => "//div[@id='mw-content-text']/table/tbody/tr/td[2]/input[@class='ui-autocomplete-loading']")
  span(:saveSitelinkLinkDisabled, :class => "wb-ui-toolbar-button-disabled")
  unordered_list(:siteIdAutocompleteList, :class => "ui-autocomplete", :index => 0)
  unordered_list(:pageAutocompleteList, :class => "ui-autocomplete", :index => 1)
  unordered_list(:editSitelinkAutocompleteList, :class => "ui-autocomplete", :index => 0)
  link(:saveSitelinkLink, :text => "save")
  link(:cancelSitelinkLink, :text => "cancel")
  link(:removeSitelinkLink, :text => "remove")
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

  def getNthSitelinksTableRow(n)
    count = 0
    sitelinksTable_element.each do |tableRow|
      #count = count+1
      if count == n
        return tableRow
      end
      count = count+1
    end
    return false
  end

  def countExistingSitelinks
    count = 0
    sitelinksTable_element.each do |tableRow|
      count = count+1
    end
    return count-2
  end

  def wait_for_sitelinks_to_load
    wait_until do
      sitelinksTable?
    end
  end

end
