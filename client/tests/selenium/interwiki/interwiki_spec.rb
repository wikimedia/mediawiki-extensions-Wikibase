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
nell_zhaf = "{{noexternallanglinks:zh|af}}"
nell_afzh = "{{noexternallanglinks:af|zh}}"
nell_itzhaf = "{{noexternallanglinks:it|zh|af}}"
nell_none = "{{noexternallanglinks}}"
item_description = "It's an American musician and singer-songwriter."
item_sitelinks = [["en", "Jimi Hendrix"], ["de", "Jimi Hendrix"], ["af", "Jimi Hendrix"], ["zh", "Jimi Hendrix"], ["it", "Jimi Hendrix"]]

describe "Check client interwiki links" do
  before :all do
    # set up: create article, create corresponding item with sitelinks
    visit_page(ClientPage) do |page|
      page.create_article(article_title, article_text, true)
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
        page.count_interwiki_links.should == item_sitelinks.count - 1
        page.interwiki_de?.should be_true
        page.interwiki_it?.should be_true
        page.interwiki_af?.should be_true
        page.interwiki_zh?.should be_true
        page.clientEditLinksLink?.should be_true
      end
    end
    it "should check correct behaviour of editLinks-Link" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.clientEditLinksLink
      end
      on_page(ItemByTitlePage) do |page|
        page.itemByTitleSubmit
      end
      on_page(ItemPage) do |page|
        page.wait_for_item_to_load
        page.itemLabelSpan.should == article_title
        page.itemDescriptionSpan.should == item_description
      end
    end
  end

  context "Check noexternallanglinks magic word behaviour" do
    it "should check noexternallanglinks zh|af" do
      on_page(ClientPage) do |page|
        page.change_article(article_title, article_text + nell_zhaf)
        page.clientArticleTitle.should == article_title
        page.count_interwiki_links.should == 2
        page.interwiki_de?.should be_true
        page.interwiki_it?.should be_true
        page.interwiki_af?.should be_false
        page.interwiki_zh?.should be_false
        page.clientEditLinksLink?.should be_true
      end
    end
    it "should check noexternallanglinks af|zh" do
      on_page(ClientPage) do |page|
        page.change_article(article_title, article_text + nell_afzh)
        page.clientArticleTitle.should == article_title
        page.count_interwiki_links.should == 2
        page.interwiki_de?.should be_true
        page.interwiki_it?.should be_true
        page.interwiki_af?.should be_false
        page.interwiki_zh?.should be_false
        page.clientEditLinksLink?.should be_true
      end
    end
    it "should check noexternallanglinks it|zh|af" do
      on_page(ClientPage) do |page|
        page.change_article(article_title, article_text + nell_itzhaf)
        page.clientArticleTitle.should == article_title
        page.count_interwiki_links.should == 1
        page.interwiki_de?.should be_true
        page.interwiki_it?.should be_false
        page.interwiki_af?.should be_false
        page.interwiki_zh?.should be_false
        page.clientEditLinksLink?.should be_true
      end
    end
    it "should check noexternallanglinks at all" do
      on_page(ClientPage) do |page|
        page.change_article(article_title, article_text + nell_none)
        page.clientArticleTitle.should == article_title
        page.interwiki_xxx?.should be_false
        page.interwiki_de?.should be_false
        page.interwiki_it?.should be_false
        page.interwiki_af?.should be_false
        page.interwiki_zh?.should be_false
      end
    end
  end

  after :all do
    # tear down: remove sitelinks, remove noexternallanglinks from article
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_sitelinks_to_load
      page.remove_all_sitelinks
    end
    on_page(ClientPage) do |page|
      page.change_article(article_title, article_text)
    end
  end
end
