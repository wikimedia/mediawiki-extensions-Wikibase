# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for client's recent changes & watchlist

require 'spec_helper'

article_title = "Tina Turner"
article_text = "American singer."
item_description = generate_random_string(20)
item_sitelinks = [["en", "Tina Turner"], ["de", "Tina Turner"]]
item_id = 0

describe "Check client RC & WL" do
  before :all do
    # set up: create article, create corresponding item with sitelinks
    visit_page(ClientPage) do |page|
      page.create_article(article_title, article_text, true)
    end
    visit_page(RepoLoginPage) do |page|
      page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
    end
    visit_page(CreateItemPage) do |page|
      item_id = page.create_new_item(article_title, item_description)
      page.add_sitelinks(item_sitelinks)
    end
    visit_page(ClientLoginPage) do |page|
      page.logout_user
    end
  end

  context "Check watchlist" do
    it "should add page to watchlist & check propagation of changes to watchlist" do
      visit_page(ClientLoginPage) do |page|
        page.login_with(CLIENT_ADMIN_USERNAME, CLIENT_ADMIN_PASSWORD)
      end
      on_page(ClientPage) do |page|
        page.watch_article(article_title)
      end
      visit_page(WatchlistPage) do |page|
        page.wlFirstResultUserLinkNoWikidata_element.text.downcase.include?(WIKI_ADMIN_USERNAME.downcase).should be_false
        page.show_wikibase
        page.wlFirstResultDiffLink?.should be_true
        page.wlFirstResultUserLink?.should be_true
        page.wlFirstResultUserLink_element.text.downcase.include?(WIKI_ADMIN_USERNAME.downcase).should be_true
        page.wlFirstResultHistoryLink?.should be_true
        page.wlFirstResultLabelLink?.should be_true
        page.wlFirstResultLabelLink_element.text.should == article_title
        page.wlFirstResultIDLink?.should be_true
        page.wlFirstResultIDLink_element.text.include?(item_id).should be_true
      end
      visit_page(WatchlistPage) do |page|
        page.show_wikibase
        page.wlFirstResultUserLink
      end
      on_page(ItemPage) do |page|
        page.mwFirstHeading.downcase.should == "user:" + WIKI_ADMIN_USERNAME.downcase
      end
      visit_page(WatchlistPage) do |page|
        page.show_wikibase
        page.wlFirstResultHistoryLink
      end
      on_page(ClientPage) do |page|
        page.clientArticleTitle.include?(article_title).should == true
        page.current_url.include?("action=history").should == true
      end
      visit_page(WatchlistPage) do |page|
        page.show_wikibase
        page.wlFirstResultDiffLink
      end
      on_page(ClientPage) do |page|
        page.clientArticleTitle.include?(article_title).should == true
        page.clientArticleTitle.include?("Difference").should == true
      end
      visit_page(WatchlistPage) do |page|
        page.show_wikibase
        page.wlFirstResultLabelLink
      end
      on_page(ClientPage) do |page|
        page.clientArticleTitle.should == article_title
      end
      visit_page(WatchlistPage) do |page|
        page.show_wikibase
        page.wlFirstResultIDLink
      end
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == article_title
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.remove_all_sitelinks
      end
      visit_page(WatchlistPage) do |page|
        @browser.refresh
        page.show_wikibase
        page.wait_until do
          page.clientFirstResultComment?
        end
        page.clientFirstResultComment.include?("Language links removed").should == true
      end
      visit_page(ClientPage) do |page|
        page.create_article("Bermuda", "Island", true)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_sitelinks(item_sitelinks)
        page.editSitelinkLink
        current_page = page.pageInputFieldExistingSiteLink
        new_page = "Bermuda"
        page.pageInputFieldExistingSiteLink = new_page
        ajax_wait
        page.wait_until do
          page.editSitelinkAutocompleteList_element.visible?
        end
        page.saveSitelinkLink
        ajax_wait
        page.wait_for_api_callback
      end
      visit_page(WatchlistPage) do |page|
        @browser.refresh
        page.show_wikibase
        page.wait_until do
          page.clientFirstResultComment?
        end
        page.clientFirstResultComment.include?("Language link changed from").should == true
      end
    end
  end

  context "Check recent changes" do
    it "should try filter wikidata entries by flag" do
      visit_page(ClientRecentChangesPage) do |page|
        sleep 1 #wikidata entries not hidden immediately
        page.rcFirstResult_element.text.include?(item_id).should be_false
        page.show_wikidata
        page.rcFirstResult_element.text.include?(item_id).should be_true
        page.hide_wikidata
        page.rcFirstResult_element.text.include?(item_id).should be_false
      end
    end
    it "should try filter wikidata entries by pref" do
      visit_page(ClientLoginPage) do |page|
        page.login_with(CLIENT_ADMIN_USERNAME, CLIENT_ADMIN_PASSWORD)
      end
      visit_page(ClientUserPrefsPage) do |page|
        page.wait_for_prefs_to_load
        page.toggleWikidataEdits(true)
      end
      visit_page(ClientRecentChangesPage) do |page|
        page.rcFirstResult_element.text.include?(item_id).should be_true
        page.hide_wikidata
        sleep 1 #wikidata entries not hidden immediately
        page.rcFirstResult_element.text.include?(item_id).should be_false
        page.show_wikidata
        page.rcFirstResult_element.text.include?(item_id).should be_true
      end
      visit_page(ClientUserPrefsPage) do |page|
        page.wait_for_prefs_to_load
        page.toggleWikidataEdits(false)
      end
      visit_page(ClientLoginPage) do |page|
        page.logout_user
      end
    end

    it "check standard entry elements" do
      visit_page(ClientRecentChangesPage) do |page|
        page.show_wikidata
        page.rcFirstResultDiffLink?.should be_true
        page.rcFirstResultUserLink?.should be_true
        page.rcFirstResultUserLink_element.text.downcase.include?(WIKI_ADMIN_USERNAME.downcase).should be_true
        page.rcFirstResultHistoryLink?.should be_true
        page.rcFirstResultLabelLink?.should be_true
        page.rcFirstResultLabelLink_element.text.should == article_title
        page.rcFirstResultIDLink?.should be_true
        page.rcFirstResultIDLink_element.text.include?(item_id).should be_true
        page.rcFirstResultComment?.should be_true

        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.rcFirstResultUserLink
        end
        on_page(ItemPage) do |page|
          page.mwFirstHeading.downcase.should == "user:" + WIKI_ADMIN_USERNAME.downcase
        end
        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.rcFirstResultHistoryLink
        end
        on_page(ClientPage) do |page|
          page.clientArticleTitle.include?(article_title).should == true
          page.current_url.include?("action=history").should == true
        end
        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.rcFirstResultDiffLink
        end
        on_page(ClientPage) do |page|
          page.clientArticleTitle.include?(article_title).should == true
          page.clientArticleTitle.include?("Difference").should == true
        end
        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.rcFirstResultLabelLink
        end
        on_page(ClientPage) do |page|
          page.clientArticleTitle.should == article_title
        end
        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.rcFirstResultIDLink
        end
        on_page(ItemPage) do |page|
          page.wait_for_entity_to_load
          page.entityLabelSpan.should == article_title
        end
      end
    end
  end

  after :all do
    # tear down: remove sitelinks, logout on repo&client, unwatch article
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.remove_all_sitelinks
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
    visit_page(ClientLoginPage) do |page|
      page.login_with(CLIENT_ADMIN_USERNAME, CLIENT_ADMIN_PASSWORD)
    end
    visit_page(ClientPage) do |page|
      page.unwatch_article(article_title)
    end
    visit_page(ClientLoginPage) do |page|
      page.logout_user
    end
  end
end
