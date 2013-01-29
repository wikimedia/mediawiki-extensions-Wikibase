# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for SetLabel special page

class SetLabelPage < SetEntityPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:SetLabel"

  button(:setLabelSubmit, :id => "wb-setlabel-submit")

end
