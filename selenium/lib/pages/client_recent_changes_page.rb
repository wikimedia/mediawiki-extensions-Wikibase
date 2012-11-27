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
  link(:clientFirstResultHistoryLink, :xpath => "//ul[@class='special']/li/a[2]")
  link(:clientFirstResultLabelLink, :xpath => "//ul[@class='special']/li/a[3]")
  link(:clientFirstResultIDLink, :xpath => "//ul[@class='special']/li/a[4]")
  link(:clientFirstResultUserLink, :xpath => "//ul[@class='special']/li/a[5]")
  span(:clientFirstResultComment, :xpath => "//ul[@class='special']/li/span[@class='comment']")
  link(:clientFirstResultCommentSitelink, :xpath => "//ul[@class='special']/li/span[@class='comment']/a")
  link(:clientFilterHideWikidata, :xpath => "//fieldset[@class='rcoptions']/a[15]")

  def hide_wikidata
    navigate_to WIKI_CLIENT_URL + "Special:RecentChanges" + "?hidewikidata=1"
  end

  def show_wikidata
    navigate_to WIKI_CLIENT_URL + "Special:RecentChanges" + "?hidewikidata=0"
  end

end
