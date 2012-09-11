# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for client's interwiki links

require 'spec_helper'

article_title = "Jimi Hendrix"
article_text = "American musician and singer-songwriter."
item_description = "It's an American musician and singer-songwriter."
item_sitelinks = [["en", "Jimi Hendrix"], ["de", "Jimi Hendrix"]]

describe "Check client interwiki links" do
  before :all do
    # set up: create article, create corresponding item with sitelinks
    visit_page(ClientPage) do |page|
      page.create_article(article_title, article_text)
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(article_title, item_description)
      page.add_sitelinks(item_sitelinks)
    end
  end

  context "Check client interwiki links" do
    it "should check if interwikilinks & the editLinks-Link are shown correctly" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.clientArticleTitle.should == article_title
        page.count_interwiki_links.should == 1
        page.interwiki_de?.should be_true
        page.clientEditLinksLink?.should be_true
      end
    end
    it "should check correct behaviour od editLinks-Link" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.clientEditLinksLink
      end
      on_page(ItemPage) do |page|
        page.wait_for_item_to_load
        page.itemLabelSpan.should == article_title
        page.itemDescriptionSpan.should == item_description
      end
    end
  end

  after :all do
    # tear down: remove sitelinks, purge client page
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_sitelinks_to_load
      page.remove_all_sitelinks
    end
    on_page(ClientPage) do |page|
      page.navigate_to_article(article_title, true)
    end
  end
end
