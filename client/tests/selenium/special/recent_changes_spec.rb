# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for client's recent changes

require 'spec_helper'

article_title = "Tina Turner"
article_text = "American singer."
item_description = generate_random_string(20)
item_sitelinks = [["en", "Tina Turner"], ["de", "Tina Turner"]]
item_id = 0

describe "Check client interwiki links" do
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

  context "Check client recent changes" do
    it "should try filter wikidata entries by flag" do
      visit_page(ClientRecentChangesPage) do |page|
        page.clientFirstResult_element.text.include?(item_id).should be_false
        page.show_wikidata
        page.clientFirstResult_element.text.include?(item_id).should be_true
        page.hide_wikidata
        page.clientFirstResult_element.text.include?(item_id).should be_false
      end
    end
    it "should try filter wikidata entries by pref" do
      visit_page(ClientLoginPage) do |page|
        page.login_with(CLIENT_ADMIN_USERNAME, CLIENT_ADMIN_PASSWORD)
      end
      visit_page(ClientUserPrefsPage) do |page|
        page.toggleWikidataEdits(true)
      end
      visit_page(ClientRecentChangesPage) do |page|
        page.clientFirstResult_element.text.include?(item_id).should be_true
        page.hide_wikidata
        page.clientFirstResult_element.text.include?(item_id).should be_false
        page.show_wikidata
        page.clientFirstResult_element.text.include?(item_id).should be_true
      end
      visit_page(ClientUserPrefsPage) do |page|
        page.toggleWikidataEdits(false)
      end
      visit_page(ClientLoginPage) do |page|
        page.logout_user
      end
    end

    it "check standard entry elements" do
      visit_page(ClientRecentChangesPage) do |page|
        page.show_wikidata
        page.clientFirstResultDiffLink?.should be_true
        page.clientFirstResultUserLink?.should be_true
        page.clientFirstResultUserLink_element.text.downcase.include?(WIKI_ADMIN_USERNAME.downcase).should be_true
        page.clientFirstResultHistoryLink?.should be_true
        page.clientFirstResultLabelLink?.should be_true
        page.clientFirstResultLabelLink_element.text.should == article_title
        page.clientFirstResultIDLink?.should be_true
        page.clientFirstResultIDLink_element.text.include?(item_id).should be_true
        page.clientFirstResultComment?.should be_true

        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.clientFirstResultUserLink
        end
        on_page(ItemPage) do |page|
          page.mwFirstHeading.downcase.should == "user:" + WIKI_ADMIN_USERNAME.downcase
        end
        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.clientFirstResultHistoryLink
        end
        on_page(ClientPage) do |page|
          page.clientArticleTitle.include?(article_title).should == true
          page.current_url.include?("action=history").should == true
        end
        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.clientFirstResultDiffLink
        end
        on_page(ClientPage) do |page|
          page.clientArticleTitle.include?(article_title).should == true
          page.clientArticleTitle.include?("Difference").should == true
        end
        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.clientFirstResultLabelLink
        end
        on_page(ClientPage) do |page|
          page.clientArticleTitle.should == article_title
        end
        visit_page(ClientRecentChangesPage) do |page|
          page.show_wikidata
          page.clientFirstResultIDLink
        end
        on_page(ItemPage) do |page|
          page.wait_for_entity_to_load
          page.entityLabelSpan.should == article_title
        end
      end
    end
  end

  after :all do
    # tear down: remove sitelinks
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.remove_all_sitelinks
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
    visit_page(ClientLoginPage) do |page|
      page.logout_user
    end
  end
end
