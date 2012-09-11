# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for ItembyTitle special page

class ItemByTitlePage < ItemPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:ItemByTitle"

  text_field(:itemByTitleSiteField, :id => "wb-itembytitle-sitename")
  text_field(:itemByTitlePageField, :id => "pagename")
  button(:itemByTitleSubmit, :css => "form#wb-itembytitle-form1 > fieldset > input[type='submit']")

end
