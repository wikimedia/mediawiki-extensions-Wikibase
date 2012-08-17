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
  page_url WIKI_CLIENT_URL + "index.php"

  #
  text_field(:clientSearchInput, :id => "searchInput")
  button(:clientSearchSubmit, :id => "searchGoButton")
  paragraph(:clientSearchNoresult, :class => "mw-search-nonefound")
  link(:clientCreateArticleLink, :xpath => "//p[@class='mw-search-createlink']/b/a")
  text_area(:clientCreateArticleInput, :id => "wpTextbox1")
  button(:clientCreateArticleSubmit, :id => "wpSave")
  span(:clientArticleTitle, :xpath => "//h1[@id='firstHeading']/span")
  unordered_list(:clientInterwikiLinkList, :xpath => "//div[@id='p-lang']/div/ul")
  button(:clientPurgeSubmit, :xpath => "//form[@class='visualClear']/input[@class='mw-htmlform-submit']")

  #language links
  link(:interwiki_de, :xpath => "//li[@class='interwiki-de']/a")
  link(:interwiki_en, :xpath => "//li[@class='interwiki-en']/a")
  link(:interwiki_it, :xpath => "//li[@class='interwiki-it']/a")
  link(:interwiki_hu, :xpath => "//li[@class='interwiki-hu']/a")
  link(:interwiki_fi, :xpath => "//li[@class='interwiki-fi']/a")
  link(:interwiki_fr, :xpath => "//li[@class='interwiki-fr']/a")
  def create_article(title, text)
    self.clientSearchInput= title
    clientSearchSubmit
    if clientSearchNoresult?
      clientCreateArticleLink
      self.clientCreateArticleInput= text
      clientCreateArticleSubmit
    end
  end

  def navigate_to_article(title, purge = false)
    param_purge = ""
    if purge
      param_purge = "?action=purge"
    end
    navigate_to WIKI_CLIENT_URL + "index.php/" + title + param_purge
    if clientPurgeSubmit?
      clientPurgeSubmit
    end
  end

  def count_interwiki_links
    count = 0
    clientInterwikiLinkList_element.each do |listElement|
      count = count+1
    end
    return count
  end

end
