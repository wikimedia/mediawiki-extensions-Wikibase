# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for client-repo connection

require 'spec_helper'

article_title = "Rome"
article_text = "It's the capital of Italy!"
item_description = "It's the capital of Italy!"
item_sitelink_en = [["en", "Rome"]]
item_sitelinks = [["de", "Rom"], ["it", "Roma"], ["fi", "Rooma"], ["hu", "Róma"]]
item_sitelinks_additional = [["fr", "Rome"]]

describe "Check functionality of client-repo connection" do
  before :all do
    # set up
  end
  context "client-repo test setup" do
    it "should create a new article on client" do
      visit_page(ClientPage) do |page|
        page.create_article(article_title, article_text)
        page.navigate_to_article(article_title)
        page.clientArticleTitle.should == article_title
        page.interwiki_xxx?.should be_false
      end
    end
    it "should create a corresponding wikidata item on the repo" do
      visit_page(ItemPage) do |page|
        page.create_new_item(article_title, item_description)
        page.itemLabelSpan.should == article_title
        page.itemDescriptionSpan.should == item_description
      end
    end
    it "should create an english sitelink for the item" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.add_sitelinks(item_sitelink_en)
        page.get_number_of_sitelinks_from_counter.should == 1
      end
    end
  end

  context "client-repo adding sitelinks" do
    it "should add some sitelinks to the item" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.add_sitelinks(item_sitelinks)
        page.get_number_of_sitelinks_from_counter.should == item_sitelinks.count + 1
      end
    end
  end

  context "client-repo checking for interwikilinks on client" do
    it "should check if interwikilinks are shown correctly on client" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.count_interwiki_links.should == 4
        page.interwiki_de?.should be_true
        page.interwiki_it?.should be_true
        page.interwiki_fi?.should be_true
        page.interwiki_hu?.should be_true
        page.interwiki_en?.should be_false
      end
    end
  end

  context "client-repo clicking on interwikilinks on client" do
    it "should check if interwikilinks lead to correct website" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.interwiki_de
        page.clientArticleTitle.should == item_sitelinks[0][1]
        page.navigate_to_article(article_title)
        page.interwiki_it
        page.clientArticleTitle.should == item_sitelinks[1][1]
        page.navigate_to_article(article_title)
        page.interwiki_fi
        page.clientArticleTitle.should == item_sitelinks[2][1]
        page.navigate_to_article(article_title)
        page.interwiki_hu
        page.clientArticleTitle.should == item_sitelinks[3][1]
      end
    end
  end

  context "client-repo adding some other sitelinks" do
    it "should add some more sitelinks" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.add_sitelinks(item_sitelinks_additional)
        page.get_number_of_sitelinks_from_counter.should == item_sitelinks.count + 1 + item_sitelinks_additional.count
      end
    end
  end

  context "client-repo checking for interwikilinks on client" do
    it "should check if additional interwikilinks are shown correctly on client" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.count_interwiki_links.should == 5
        page.interwiki_de?.should be_true
        page.interwiki_it?.should be_true
        page.interwiki_fi?.should be_true
        page.interwiki_hu?.should be_true
        page.interwiki_fr?.should be_true
        page.interwiki_en?.should be_false
      end
    end
  end

  context "client-repo removing the sitelinks from the repo and checking that they're gone on the client" do
    it "should remove all sitelinks" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.remove_all_sitelinks
      end
    end

    it "should check that no sitelinks are displayed on client" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title, true)
        page.interwiki_xxx?.should be_false
      end
    end
  end
  after :all do
    # tear down
  end
end
