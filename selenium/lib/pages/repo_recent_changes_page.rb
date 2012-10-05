# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for repo recent changes special page

class RepoRecentChangesPage < RecentChangesPage
  include PageObject
  page_url WIKI_REPO_URL + "Special:RecentChanges"
end
