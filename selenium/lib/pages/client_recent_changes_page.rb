# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for client recent changes special page

class ClientRecentChangesPage < RecentChangesPage
  include PageObject
  page_url WIKI_CLIENT_URL + "Special:RecentChanges"

  list_item(:clientFirstResult, :xpath => "//ul[@class='special']/li")
  link(:clientFirstResultDiffLink, :xpath => "//ul[@class='special']/li/a[1]")
  link(:clientFirstResultLabelLink, :xpath => "//ul[@class='special']/li/a[3]")
  link(:clientFirstResultIDLink, :xpath => "//ul[@class='special']/li/a[4]")
  link(:clientFirstResultUserLink, :xpath => "//ul[@class='special']/li/a[5]")

end
