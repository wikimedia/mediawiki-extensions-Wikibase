# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Kreuz
# License:: GNU GPL v2+
#
# module for the Special:ModifyEntity page

module SpecialModifyEntityModule
  include PageObject

  p(:anonymous_edit_warning, class: 'warning')
  p(:error_message, class: 'error')
  text_field(:id_input_field, css: 'input#wb-modifyentity-id, #wb-modifyentity-id input')
end
