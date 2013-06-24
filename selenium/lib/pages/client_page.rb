# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for wikidata client page

require 'ruby_selenium'

class ClientPage < RubySelenium
  include PageObject
  page_url WIKI_CLIENT_URL

  text_field(:clientSearchInput, :id => "searchInput")
  button(:clientSearchSubmit, :id => "searchGoButton")
  button(:clientSearchSubmitFancy, :id => "searchButton")
  paragraph(:clientSearchNoresult, :class => "mw-search-nonefound")
  link(:clientCreateArticleLink, :xpath => "//p[@class='mw-search-createlink']/b/a")
  link(:clientEditArticleLink, :xpath => "//li[@id='ca-edit']/span/a")
  link(:clientEditLinksLink, :xpath => "//li[@class='wbc-editpage']/a")
  link(:clientActionsMenu, :xpath => "//div[@id='p-cactions']/h5/a")
  link(:clientWatchArticle, :xpath => "//li[@id='ca-watch']/a")
  link(:clientUnwatchArticle, :xpath => "//li[@id='ca-unwatch']/a")
  link(:clientDataItemLink, :id => "t-wikibase")
  text_area(:clientCreateArticleInput, :id => "wpTextbox1")
  button(:clientCreateArticleSubmit, :id => "wpSave")
  span(:clientArticleTitle, :xpath => "//h1[@id='firstHeading']/span")
  paragraph(:clientArticleText, :xpath => "//div[@id='mw-content-text']/p")
  unordered_list(:clientInterwikiLinkList, :xpath => "//div[@id='p-lang']/div/ul")
  button(:clientActionConfirmationButton, :xpath => "//form[@class='visualClear']/input[@class='mw-htmlform-submit']")

  #link item dialog
  span(:clientLinkDialogHeader, :xpath => "//div[contains(@class, 'ui-dialog')]/div[contains(@class, 'ui-dialog-titlebar')]/span[contains(@class, 'ui-dialog-title')]")
  link(:clientLinkDialogClose, :class => "ui-dialog-titlebar-close")
  link(:clientLinkItemLink, :id => "wbc-linkToItem-link")
  text_field(:clientLinkItemLanguageInput, :id => "wbclient-linkItem-Site")
  text_field(:clientLinkItemLanguagePage, :id => "wbclient-linkItem-page")
  button(:clientLinkItemSubmit, :id => "wbclient-linkItem-goButton")
  link(:clientLinkItemLanguageSelectorFirst, :xpath => "//ul[contains(@class, 'wikibase-siteselector-list')]/li/a")
  link(:clientLinkItemSuccess, :xpath => "//p[contains(@class, 'wbclient-linkItem-success-message')]/a")

  #language links
  link(:interwiki_de, :xpath => "//li[@class='interwiki-de']/a")
  link(:interwiki_en, :xpath => "//li[@class='interwiki-en']/a")
  link(:interwiki_it, :xpath => "//li[@class='interwiki-it']/a")
  link(:interwiki_hu, :xpath => "//li[@class='interwiki-hu']/a")
  link(:interwiki_fi, :xpath => "//li[@class='interwiki-fi']/a")
  link(:interwiki_fr, :xpath => "//li[@class='interwiki-fr']/a")
  link(:interwiki_af, :xpath => "//li[@class='interwiki-af']/a")
  link(:interwiki_zh, :xpath => "//li[@class='interwiki-zh']/a")
  link(:interwiki_xxx, :xpath => "//li[contains(@class, 'interwiki')]/a")
  #methods
  def create_article(title, text, overwrite = false)
    self.clientSearchInput = title
    if clientSearchSubmit?
      clientSearchSubmit
    else
      clientSearchSubmitFancy
    end
    if clientSearchNoresult?
      clientCreateArticleLink
      self.clientCreateArticleInput = text
      clientCreateArticleSubmit
    elsif overwrite
      clientEditArticleLink
      self.clientCreateArticleInput = text
      clientCreateArticleSubmit
    end
  end

  def change_article(title, text)
    navigate_to_article(title)
    clientEditArticleLink
    self.clientCreateArticleInput = text
    clientCreateArticleSubmit
  end

  def navigate_to_article(title, purge = false)
    param_purge = ""
    if purge
      param_purge = "?action=purge"
    end
    url = WIKI_CLIENT_URL + title + param_purge
    navigate_to url
    if clientActionConfirmationButton?
      clientActionConfirmationButton
    end
    sleep 1
    @browser.refresh
  end

  def count_interwiki_links
    count = 0
    clientInterwikiLinkList_element.each do |listElement|
      count = count+1
    end
    return count-1 # decrement by 1 because "edit-link" is always shown
  end

  def watch_article(title)
    navigate_to_article(title)
    navigate_to(current_url + "?action=watch")
    if clientActionConfirmationButton?
      clientActionConfirmationButton
    end
  end

  def unwatch_article(title)
    navigate_to_article(title)
    navigate_to(current_url + "?action=unwatch")
    if clientActionConfirmationButton?
      clientActionConfirmationButton
    end
  end

  def wait_for_link_item_link
    wait_until do
      clientLinkItemLink?
    end
  end

  def wait_for_link_item_dialog
    wait_until do
      clientLinkDialogHeader?
    end
  end
end
