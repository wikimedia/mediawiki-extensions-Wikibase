# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for ItemDisambiguation special page

class ItemDisambiguationPage < ItemPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:ItemDisambiguation"

  text_field(:disambiguationLanguageField, :id => "wb-itemdisambiguation-languagename")
  text_field(:disambiguationLabelField, :id => "labelname")
  button(:disambiguationSubmit, :css => "form#mw-itemdisambiguation-form1 > fieldset > input[type='submit']")
  unordered_list(:disambiguationList, :class => "wikibase-disambiguation")
  span(:disambiguationItemLink1, :class => "wb-itemlink-id", :index => 0)
  span(:disambiguationItemLink2, :class => "wb-itemlink-id", :index => 1)
  def countDisambiguationElements
    count = 0
    disambiguationList_element.each do |disambigationElem|
      count = count + 1
    end
    return count
  end
end
