# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# base page object for Contributions special page

class ContributionsPage < ItemPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:Contributions" + "/" + WIKI_ADMIN_USERNAME

  unordered_list(:contributions, :css => "div.mw-content-text > ul:nth-of-type(1)")
  span(:firstResultLabelSpan, :class => "wb-itemlink-label")
  span(:firstResultIdSpan, :class => "wb-itemlink-id")
  link(:firstResultLink, :class => "mw-contributions-title")

end

