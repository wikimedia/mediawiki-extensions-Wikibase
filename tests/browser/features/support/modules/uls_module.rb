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
  a(:uls_open, xpath: "//li[@id='pt-uls']/a")
  text_field(:uls_language_filter, id: 'languagefilter')
  a(:uls_language_link, xpath: "//div[contains(@class, 'uls-language-block')]/ul/li/a")
  div(:uls_div, class: 'uls-menu')

  a(:view_tab_link, xpath: "//li[@id='ca-view']/span/a")
  a(:recent_changes_link, xpath: "//li[@id='n-recentchanges']/a")
  a(:special_page_tab_link, xpath: "//li[@id='ca-nstab-special']/span/a")
  a(:first_result_link, xpath: "//span[@class='mw-title']/a")
  # ULS
  def uls_switch_language(code, name)
    if uls_open? == false
      nouls_switch_language(code)
      return
    end
    if uls_open_element.text.downcase != name.downcase
      uls_open
      uls_language_filter_element.when_present
      self.uls_language_filter = name
      ajax_wait
      uls_language_link
    end
  end

  # NO ULS
  def nouls_switch_language(code)
    url = current_url
    uselang = 'uselang=' + code
    if url.include? '?'
      new_url = url + '&' + uselang
    else
      new_url = url + '?' + uselang
    end
    navigate_to new_url
  end
end
