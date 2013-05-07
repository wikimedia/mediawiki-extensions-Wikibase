# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# module for ULS page

module ULSPage
  include PageObject
  # ULS UI elements
  link(:ulsOpen, :xpath => "//li[@id='pt-uls']/a")
  text_field(:ulsLanguageFilter, :id => "languagefilter")
  link(:ulsLanguageLink, :xpath => "//div[contains(@class, 'uls-language-block')]/ul/li/a")
  div(:ulsDiv, :class => "uls-menu")

  link(:viewTabLink, :xpath => "//li[@id='ca-view']/span/a")
  link(:recentChangesLink, :xpath => "//li[@id='n-recentchanges']/a")
  link(:specialPageTabLink, :xpath => "//li[@id='ca-nstab-special']/span/a")
  link(:firstResultLink, :xpath => "//span[@class='mw-title']/a")
  # ULS
  def uls_switch_language(code, name)
    if ulsOpen? == false
      self.nouls_switch_language(code)
      return
    end
    if ulsOpen_element.text.downcase != name.downcase
      ulsOpen
      self.ulsLanguageFilter= name
      ajax_wait
      ulsLanguageLink
    end
  end

  # NO ULS
  def nouls_switch_language(code)
    url = current_url
    uselang = "uselang=" + code
    if url.include? "?"
      new_url = url + "&" + uselang
    else
      new_url = url + "?" + uselang
    end
    navigate_to new_url
  end
end
