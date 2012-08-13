# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for language switcher

require 'ruby_selenium'

class LanguageSelectorPage < NewItemPage
  include PageObject

  # language switcher links
  link(:ulsOpen, :xpath => "//li[@id='pt-uls']/a")
  text_field(:ulsLanguageFilter, :id => "languagefilter")

  # language specific elements
  link(:viewTabLink, :xpath => "//li[@id='ca-view']/span/a")
  link(:recentChangesLink, :xpath => "//li[@id='n-recentchanges']/a")
  link(:specialPageTabLink, :xpath => "//li[@id='ca-nstab-special']/span/a")
  link(:firstResultLink, :xpath => "//span[@class='mw-title']/a")
  
  def ulsSwitchLanguage(lang)
    ulsOpen
    self.ulsLanguageFilter= lang
    ajax_wait
    ulsLanguageFilter_element.send_keys :return
  end
end
