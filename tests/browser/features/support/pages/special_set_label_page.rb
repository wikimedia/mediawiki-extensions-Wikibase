# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Kreuz
# License:: GNU GPL v2+
#
# page object for the Special:SetLabel page

class SpecialSetLabelPage
  include PageObject
  include SpecialModifyTermModule

  page_url URL.repo_url('Special:SetLabel')

  button(:set_label_button, css: 'input#wb-setlabel-submit, #wb-setlabel-submit button')
end
