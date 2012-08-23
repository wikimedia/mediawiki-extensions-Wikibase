# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for a protect page action

class ProtectedPage < ItemPage
  include PageObject

  button(:protect_submit, :id => 'mw-Protect-submit')
  select_list(:protection_level, :id => 'mwProtect-level-edit' )

  def protect_page
    navigate_to(@@item_url + "?action=protect")
    self.protection_level= "Administrators only"
    protect_submit
  end

  def unprotect_page
      navigate_to(@@item_url + "?action=unprotect")
      self.protection_level= "Allow all users"
      protect_submit
    end
end
