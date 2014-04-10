# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Mättig (thiemo.maettig@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for the Special:SetLabel page

class SpecialSetLabelPage < SpecialModifyTermPage
  include PageObject

  page_url URL.repo_url("Special:SetLabel")

  button(:set_label_button, id: "wb-setlabel-submit")

end
