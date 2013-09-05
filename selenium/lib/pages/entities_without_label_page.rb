# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for EntitiesWithoutLabel special page

class EntitiesWithoutLabelPage < ItemPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:EntitiesWithoutLabel"

  text_field(:languageField, :name => "language")
  button(:entitiesWithoutLabelSubmit, :css => "form#wb-entitieswithoutpage-form > fieldset > p > input[type='submit']")

  link(:listItemLink, :css => "ol.special > li:nth-of-type(1) > a:nth-of-type(1)")

end

