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

  list_item(:rcFirstResult, :xpath => "//ul[@class='special']/li")
  link(:rcFirstResultDiffLink, :xpath => "//ul[@class='special']/li/a[1]")
  link(:rcFirstResultHistoryLink, :xpath => "//ul[@class='special']/li/a[2]")
  link(:rcFirstResultLabelLink, :xpath => "//ul[@class='special']/li/a[3]")
  link(:rcFirstResultIDLink, :xpath => "//ul[@class='special']/li/a[4]")
  link(:rcFirstResultUserLink, :xpath => "//ul[@class='special']/li/a[5]")
  span(:rcFirstResultComment, :xpath => "//ul[@class='special']/li/span[@class='comment']")
  link(:rcFirstResultCommentSitelink, :xpath => "//ul[@class='special']/li/span[@class='comment']/a")
  link(:rcFilterHideWikidata, :xpath => "//fieldset[@class='rcoptions']/a[15]")
  element(:rcFirstResultWDFlag, :abbr, :xpath => "//ul[@class='special']/li/abbr")

  def hide_wikidata
    navigate_to WIKI_CLIENT_URL + "Special:RecentChanges" + "?hidewikidata=1"
  end

  def show_wikidata
    navigate_to WIKI_CLIENT_URL + "Special:RecentChanges" + "?hidewikidata=0"
  end

end
