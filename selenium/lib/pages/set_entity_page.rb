# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for SetEntity base class

class SetEntityPage < ItemPage
  include PageObject

  text_field(:idField, :id => "wb-setentity-id")
  text_field(:languageField, :id => "wb-setentity-language")
  text_field(:valueField, :id => "wb-setentity-value")

end
