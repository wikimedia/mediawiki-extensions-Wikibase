# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Mättig (thiemo.maettig@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for the Special:SetLabel page

class SpecialSetLabelPage
  include PageObject

  page_url URL.repo_url("Special:SetLabel")

  p(:anonymous_edit_warning, :class => 'warning')
  text_field(:id_input_field, :id => 'wb-modifyentity-id')
  text_field(:language_input_field, :id => 'wb-modifyterm-language')
  text_field(:label_input_field, :id => 'wb-modifyterm-value')
  button(:set_label_button, :id => 'wb-setlabel-submit')

end
