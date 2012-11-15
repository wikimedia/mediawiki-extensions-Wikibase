# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for EntitiesWithoutLabel special page

class EntitiesWithoutLabelPage < EntityPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:EntitiesWithoutLabel"

  text_field(:languageField, :name => "language")
  button(:entitiesWithoutLabelSubmit, :css => "form#wb-entitieswithoutlabel-form > fieldset > p > input[type='submit']")

  span(:itemLinkSpan, :xpath => "//span[@class='wb-itemlink-label']")

end
