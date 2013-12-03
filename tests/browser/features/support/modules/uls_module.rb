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
  a(:ulsOpen, xpath: "//li[@id='pt-uls']/a")
  text_field(:ulsLanguageFilter, id: "languagefilter")
  a(:ulsLanguageLink, xpath: "//div[contains(@class, 'uls-language-block')]/ul/li/a")
  div(:ulsDiv, class: "uls-menu")

  a(:viewTabLink, xpath: "//li[@id='ca-view']/span/a")
  a(:recentChangesLink, xpath: "//li[@id='n-recentchanges']/a")
  a(:specialPageTabLink, xpath: "//li[@id='ca-nstab-special']/span/a")
  a(:firstResultLink, xpath: "//span[@class='mw-title']/a")
  # ULS
  def uls_switch_language(code, name)
    if ulsOpen? == false
      self.nouls_switch_language(code)
      return
    end
    if ulsOpen_element.text.downcase != name.downcase
      ulsOpen
      self.ulsLanguageFilter_element.when_present
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
