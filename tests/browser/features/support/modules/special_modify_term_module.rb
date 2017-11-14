# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Kreuz
# License:: GNU GPL v2+
#
# module for the Special:ModifyTerm page

module SpecialModifyTermModule
  include PageObject
  include SpecialModifyEntityModule

  text_field(:language_input_field, css: 'input#wb-modifyterm-language, #wb-modifyterm-language input')
  text_field(:term_input_field, css: 'input#wb-modifyterm-value, #wb-modifyterm-value input')
end
