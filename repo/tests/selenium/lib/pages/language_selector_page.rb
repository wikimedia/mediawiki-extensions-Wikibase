# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for STTL language switcher

require 'ruby_selenium'

class LanguageSelectorPage < NewItemPage
  include PageObject

  # language switcher links
  link(:sttlLinkDe, :xpath => "//li[@class='sttl-lang-de sttl-toplang']/a")
  link(:sttlLinkEn, :xpath => "//li[@class='sttl-lang-en sttl-toplang']/a")

  # language specific elements
  link(:viewTabLink, :xpath => "//li[@id='ca-view']/span/a")
  link(:recentChangesLink, :xpath => "//li[@id='n-recentchanges']/a")
  link(:specialPageTabLink, :xpath => "//li[@id='ca-nstab-special']/span/a")
  link(:firstResultLink, :xpath => "//ul[@class='special']/li/a[3]")
end
