# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Mättig (thiemo.maettig@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for the Special:ModifyTerm page

module SpecialModifyTermPage
  include PageObject
  include SpecialModifyEntityPage

  text_field(:language_input_field, id: "wb-modifyterm-language")
  text_field(:term_input_field, id: "wb-modifyterm-value")

end
