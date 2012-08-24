# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for item page

require 'ruby_selenium'

class ItemPage < RubySelenium
  include PageObject

  page_url WIKI_REPO_URL + "index.php/Special:CreateItem"
  @@item_url = ""
  @@item_id = ""

  # ***** ACESSORS *****
  # new item
  div(:newItemNotification, :id => "wb-specialcreateitem-newitemnotification")

  # label UI
  h1(:firstHeading, :xpath => "//h1[contains(@class, 'wb-firstHeading')]")
  div(:uiToolbar, :class => "wb-ui-toolbar")
  span(:itemLabelSpan, :xpath => "//h1[contains(@class, 'wb-firstHeading')]/span/span")
  link(:editLabelLink, :css => "h1.wb-firstHeading > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  link(:editLabelLinkDisabled, :css => "h1.wb-firstHeading > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(1)")
  text_field(:labelInputField, :xpath => "//h1[contains(@class, 'wb-firstHeading')]/span/span/input")
  link(:cancelLabelLink, :css => "h1.wb-firstHeading > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(2)")
  link(:saveLabelLinkDisabled, :css => "h1.wb-firstHeading > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(1)")
  link(:cancelLabelLinkDisabled, :css => "h1.wb-firstHeading > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(2)")
  link(:saveLabelLink, :css => "h1.wb-firstHeading > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")

  # description UI
  span(:itemDescriptionSpan, :xpath => "//div[contains(@class, 'wb-ui-descriptionedittool')]/span[contains(@class, 'wb-property-container-value')]/span")
  link(:editDescriptionLink, :css => "div.wb-ui-descriptionedittool > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  link(:editDescriptionLinkDisabled, :css => "div.wb-ui-descriptionedittool > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button-disabled:nth-child(1)")
  text_field(:descriptionInputField, :xpath => "//div[contains(@class, 'wb-ui-descriptionedittool')]/span[contains(@class, 'wb-property-container-value')]/span/input")
  link(:cancelDescriptionLink, :css => "div.wb-ui-descriptionedittool > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(2)")
  link(:saveDescriptionLinkDisabled, :css => "div.wb-ui-descriptionedittool > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button:nth-child(1)")
  link(:cancelDescriptionLinkDisabled, :css => "div.wb-ui-descriptionedittool > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > span.wb-ui-toolbar-button:nth-child(2)")
  link(:saveDescriptionLink, :css => "div.wb-ui-descriptionedittool > span > span.wb-ui-propertyedittool-editablevalue-toolbarparent > div.wb-ui-toolbar > div.wb-ui-toolbar-group > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")

  span(:apiCallWaitingMessage, :class => "wb-ui-propertyedittool-editablevalue-waitmsg")

  # edit-tab
  list_item(:editTab, :id => "ca-edit")

  # tooltips & error tooltips
  div(:wbTooltip, :class => "tipsy-inner")
  div(:wbErrorDiv, :class => "wb-tooltip-error-top-message")
  div(:wbErrorDetailsDiv, :class => "wb-tooltip-error-details")
  link(:wbErrorDetailsLink, :class => "wb-tooltip-error-details-link")

  # language links UI
  table(:sitelinksTable, :class => "wb-sitelinks")
  link(:addSitelinkLink, :css => "table.wb-sitelinks > tfoot > tr > td > div.wb-ui-toolbar > div.wb-ui-toolbar-group > a.wb-ui-toolbar-button:nth-child(1)")
  span(:siteLinkCounter, :class => "wb-ui-propertyedittool-counter")
  text_field(:siteIdInputField, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody/tr/td[1]/input")
  text_field(:pageInputField, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody/tr/td[2]/input")
  text_field(:siteIdInputFieldLoading, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody/tr/td[1]/input[@class='ui-autocomplete-loading']")
  text_field(:pageInputFieldLoading, :xpath => "//table[contains(@class, 'wb-sitelinks')]/tbody/tr/td[2]/input[@class='ui-autocomplete-loading']")
  span(:saveSitelinkLinkDisabled, :class => "wb-ui-toolbar-button-disabled")
  unordered_list(:siteIdAutocompleteList, :class => "ui-autocomplete", :index => 0)
  unordered_list(:pageAutocompleteList, :class => "ui-autocomplete", :index => 1)
  unordered_list(:editSitelinkAutocompleteList, :class => "ui-autocomplete", :index => 0)
  link(:saveSitelinkLink, :text => "save")
  link(:cancelSitelinkLink, :text => "cancel")
  link(:removeSitelinkLink, :xpath => "//td[contains(@class, 'wb-ui-propertyedittool-editablevalue-toolbarparent')]/div/div/div/a[2]")
  link(:editSitelinkLink, :xpath => "//td[contains(@class, 'wb-ui-propertyedittool-editablevalue-toolbarparent')]/div/div/div/a")
  link(:pageArticleNormalized, :css => "td.wb-sitelinks-link-sr > a")
  link(:germanSitelink, :xpath => "//td[@class='wb-sitelinks-link wb-sitelinks-link-de']/a")
  link(:englishSitelink, :xpath => "//td[@class='wb-sitelinks-link wb-sitelinks-link-en']/a")
  span(:articleTitle, :xpath => "//h1[@id='firstHeading']/span")

  # aliases UI
  div(:aliasesDiv, :class => "wb-aliases")
  span(:aliasesTitle, :class => "wb-aliases-label")
  unordered_list(:aliasesList, :class => "wb-aliases-container")
  link(:addAliases, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool']/div[contains(@class, 'wb-ui-propertyedittool-toolbar')]/div/a[text()='add']")
  span(:addAliasesDisabled, :xpath => "//div[@class='wb-aliases wb-ui-propertyedittool wb-ui-aliasesedittool']/div[contains(@class, 'wb-ui-propertyedittool-toolbar')]/div/span")
  link(:editAliases, :xpath => "//div[contains(@class, 'wb-aliases')]/div/span[contains(@class, 'wb-ui-propertyedittool-editablevalue')]/span/div/div/div/a[text()='edit']")
  link(:saveAliases, :xpath => "//div[contains(@class, 'wb-aliases')]/div/span[contains(@class, 'wb-ui-propertyedittool-editablevalue')]/span/div/div/div/a[text()='save']")
  link(:cancelAliases, :xpath => "//div[contains(@class, 'wb-aliases')]/div/span[contains(@class, 'wb-ui-propertyedittool-editablevalue')]/span/div/div/div/a[text()='cancel']")
  text_field(:aliasesInputFirst, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all wb-aliases-alias']/span/input")
  link(:aliasesInputFirstRemove, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all wb-aliases-alias']/a[@class='tagadata-close']")
  text_field(:aliasesInputEmpty, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-empty']/span/input")
  text_field(:aliasesInputModified, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-modified']/span/input")
  text_field(:aliasesInputEqual, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-equal']/span/input")
  link(:aliasesInputRemove, :xpath => "//li[@class='tagadata-choice ui-widget-content ui-state-default ui-corner-all tagadata-choice-modified']/a[@class='tagadata-close']")

  # ULS
  link(:ulsOpen, :xpath => "//li[@id='pt-uls']/a")
  text_field(:ulsLanguageFilter, :id => "languagefilter")
  link(:viewTabLink, :xpath => "//li[@id='ca-view']/span/a")
  link(:recentChangesLink, :xpath => "//li[@id='n-recentchanges']/a")
  link(:specialPageTabLink, :xpath => "//li[@id='ca-nstab-special']/span/a")
  link(:firstResultLink, :xpath => "//span[@class='mw-title']/a")
  # ***** METHODS *****
  # new item
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
    @@item_id = @@item_url[@@item_url.index('Data:Q')+6..-1]
    navigate_to_item
    wait_for_item_to_load
  end

  def wait_for_new_item_creation
    wait_until do
      newItemNotification_element.visible?
    end
  end

  # item url navigation
  def navigate_to_item
    navigate_to @@item_url
  end

  def navigate_to_item_en
    navigate_to @@item_url + "?uselang=en"
  end

  def navigate_to_item_de
    navigate_to @@item_url + "?uselang=de"
  end

  def get_item_id
    @@item_id
  end

  # item
  def wait_for_item_to_load
    wait_until do
      uiToolbar_element.visible?
    end
  end

  def wait_for_api_callback
    #TODO: workaround for weird error randomly claiming that apiCallWaitingMessage-element is not attached to the DOM anymore
    sleep 1
    return
    wait_until do
      apiCallWaitingMessage? == false
    end
  end

  def wait_for_editLabelLink
    wait_until do
      editLabelLink?
    end
  end

  # sitelinks
  def getNumberOfSitelinksFromCounter
    wait_until do
      siteLinkCounter?
    end
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
      #don't count here to skip the table header
      #count = count+1
      if count == n
        return tableRow
      end
      #count here instead
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

  def add_sitelink(lang_code, article_title)
    addSitelinkLink
    self.siteIdInputField= lang_code
    self.pageInputField= article_title
    saveSitelinkLink
    ajax_wait
    wait_for_api_callback
  end

  def remove_all_sitelinks
    count = 0
    number_of_sitelinks = getNumberOfSitelinksFromCounter
    while count < (number_of_sitelinks)
      removeSitelinkLink
      ajax_wait
      wait_for_api_callback
      count = count + 1
    end
  end

  # aliases
  def wait_for_aliases_to_load
    wait_until do
      aliasesDiv?
    end
  end

  def countExistingAliases
    count = 0
    aliasesList_element.each do |aliasElem|
      count = count+1
    end
    return count
  end

  def getNthAlias n
    count = 1
    if aliasesList_element.exists?
      aliasesList_element.each do |aliasElem|
        if count == n
          return aliasElem
        end
        count = count+1
      end
    end
    return false
  end

  # ULS
  def ulsSwitchLanguage(lang)
    ulsOpen
    self.ulsLanguageFilter= lang
    ajax_wait
    ulsLanguageFilter_element.send_keys :return
  end

end
