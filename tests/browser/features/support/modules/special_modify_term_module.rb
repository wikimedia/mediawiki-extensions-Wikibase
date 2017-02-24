# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Mättig
# License:: GNU GPL v2+
#
# module for the Special:ModifyTerm page

module SpecialModifyTermModule
  include PageObject
  include SpecialModifyEntityModule

  text_field(:language_input_field, css: '#wb-modifyterm-language .oo-ui-inputWidget-input')
  text_field(:term_input_field, css: '#wb-modifyterm-value .oo-ui-inputWidget-input')
end
