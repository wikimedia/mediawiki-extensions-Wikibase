require 'ruby_selenium'

class AliasesItemPage # < NewItemPage
  include PageObject
  page_url "http://localhost/mediawiki/index.php/Data:Q159?uselang=en"
  # aliases UI
  div(:aliasesDiv, :class => "wb-aliases")
  span(:aliasesTitle, :class => "wb-aliases-label")
  unordered_list(:aliasesList, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool']/span[2]/div/ul")
  link(:editAliases, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool']/span[2]/span/div/div/div/a[text()='edit']")
  link(:saveAliases, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool']/span[2]/span/div/div/div/a[text()='save']")
  link(:cancelAliases, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool']/span[2]/span/div/div/div/a[text()='cancel']")
  text_field(:aliasesInputEmpty, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-empty']/span/input")
  text_field(:aliasesInputModified, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-empty']/span/input")
  def wait_for_aliases_to_load
    wait_until do
      aliasesDiv?
    end
  end

  def countExistingAliases
    count = 0
    aliasesList_element.each do |aliasElem|
      count = count+1
      # puts count
    end
    return count
  end

  def getLastAlias
    listElem
    aliasesList_element.each do |aliasElem|
      listElem = aliasElem
    end
    return listElem
  end
end
