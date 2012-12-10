# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for user preferences

class ClientUserPrefsPage
  include PageObject
  page_url WIKI_CLIENT_URL + "Special:Preferences"

  link(:recentChangesTab, :id => "preftab-rc")
  checkbox(:wikidataEntriesCheckbox, :id => "mw-input-wprcshowwikidata")
  button(:savePrefs, :id => "prefcontrol")

  def toggleWikidataEdits(on)
    recentChangesTab
    if(on == true)
      self.check_wikidataEntriesCheckbox
    else
      self.uncheck_wikidataEntriesCheckbox
    end
    savePrefs
  end

  def wait_for_prefs_to_load
      wait_until do
        recentChangesTab?
      end
    end

end
