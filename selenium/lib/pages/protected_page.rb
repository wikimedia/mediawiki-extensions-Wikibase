# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for a protect page action

class ProtectedPage < ItemPage
  include PageObject

  button(:protectSubmit, :id => 'mw-Protect-submit')
  select_list(:protectionLevel, :id => 'mwProtect-level-edit' )

  def protect_page
    navigate_to(@@item_url + "?action=protect")
    self.protectionLevel= "Allow only administrators"
    protectSubmit
  end

  def unprotect_page
      navigate_to(@@item_url + "?action=unprotect")
      self.protectionLevel= "Allow all users"
      protectSubmit
    end
end
