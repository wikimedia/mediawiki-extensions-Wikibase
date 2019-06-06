# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Thiemo Kreuz
# License:: GNU GPL v2+
#
# page object for the Special:ModifyTerm page

class SpecialModifyTermPage
  include PageObject
  include SpecialModifyTermModule
  include SpecialModifyEntityModule
end
