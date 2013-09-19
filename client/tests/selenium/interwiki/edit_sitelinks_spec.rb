# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for edit sitelinks from client

require 'spec_helper'

article_title = "Vienna"
article_text = "It's the capital of Austria!"
item_description = generate_random_string(20)
item_sitelink_en = [["en", "Vienna"]]
item_sitelink_de = [["de", "Wien"]]
item_url = ''

describe "Check functionality of editing sitelinks on client" do
  before :all do
    # set up: create article & item
    visit_page(ClientPage) do |page|
      page.create_article(article_title, article_text, true)
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
    visit_page(ClientLoginPage) do |page|
      page.login_with(CLIENT_ADMIN_USERNAME, CLIENT_ADMIN_PASSWORD)
    end
  end

  context "client: editing sitelinks" do
    it "should check article and that there are no interwikilinks yet" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.clientArticleTitle.should == article_title
        page.interwiki_xxx?.should be_false
      end
    end
    it "check button behavior of sitelink editor (not logged in)" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.wait_for_link_item_link
        page.clientLinkItemLink?.should be_true
        page.clientLinkItemLink
        page.wait_for_link_item_dialog
        page.clientLinkItemLink?.should be_true
        page.clientLinkDialogHeader.should == "You need to be logged in"
        page.clientLinkDialogClose?.should be_true
        page.clientLinkDialogClose
        page.clientLinkItemLink?.should be_true
      end
    end
    it "check button behavior of sitelink editor (logged in)" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.wait_for_link_item_link
        page.clientLinkItemLink?.should be_true
        page.clientLinkItemLink
        page.wait_for_link_item_dialog
        page.clientLinkDialogHeader.should == "Link with page"
        page.clientLinkDialogClose?.should be_true
        page.clientLinkDialogClose
        page.clientLinkDialogHeader?.should be_false
        page.clientLinkItemLink?.should be_true
      end
    end
    it "check linking an item" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.wait_for_link_item_link
        page.clientLinkItemLink
        page.wait_for_link_item_dialog
        page.clientLinkItemLanguageInput?.should be_true
        page.clientLinkItemLanguagePage?.should be_true
        page.clientLinkItemSubmit?.should be_true
        page.clientLinkDialogClose?.should be_true
        page.clientLinkItemLanguageInput = "Deutsch (dewiki)"
        ajax_wait
        page.clientLinkItemLanguageSelectorFirst?.should be_true
        page.clientLinkItemLanguageSelectorFirst
        page.clientLinkItemLanguagePage = item_sitelink_de[0][1]
        ajax_wait
        page.clientLinkItemSubmit?.should be_true
        page.clientLinkItemSubmit
        ajax_wait
        page.wait_until do
          page.clientLinkItemSuccess?
        end
        page.clientLinkItemSuccess?.should be_true
        page.clientLinkItemSubmit?.should be_true
        page.clientLinkItemSubmit
        page.count_interwiki_links.should == 1
        page.interwiki_de?.should be_true
        page.clientEditLinksLink?.should be_true
      end
    end
    it "check edit-links link on client" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.clientEditLinksLink?.should be_true
        page.clientEditLinksLink
      end
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        item_url = page.current_url
        page.entityLabelSpan.should == article_title
        page.count_existing_sitelinks.should == 2
        page.germanSitelink?.should be_true
        page.englishSitelink?.should be_true
      end
    end
  end

  after :all do
    # tear down: remove all sitelinks & logout
    on_page(ItemPage) do |page|
      page.navigate_to item_url
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
