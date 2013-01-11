# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for SetLabel special page

class SetLabelPage < ItemPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:SetLabel"

  text_field(:idField, :name => "id")
  text_field(:languageField, :name => "language")
  text_field(:labelField, :name => "label")
  #button(:setLabelSubmit, :css => "form#wb-setlabel-form1 > input[type='submit']")
  button(:setLabelSubmit, :id => "wb-setlabel-submit")

end
