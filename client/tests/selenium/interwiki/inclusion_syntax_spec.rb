# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for inclusion syntax on the client

require 'spec_helper'

article_title = "Barry Gibb"
article_text = "Member of the Bee Gees."
item_sitelinks = [["en", "Barry Gibb"], ["de", "Barry Gibb"]]
item_label1 = "Robin Gibb"
item_label2 = "Bee Gees"
item_property_brother = "brother"
item_property_member_of = "member of"
item_property_brother_url = ""
item_property_member_of_url = ""
include_property_brother = "{{#property:" + item_property_brother + "}}"
include_property_member_of = "{{#property:" + item_property_member_of + "}}"
article_text_extended = " Together with his brother " + include_property_brother + " he founded the band " + include_property_member_of + "."
article_text_valid = "Member of the Bee Gees. Together with his brother " + item_label1 + " he founded the band " + item_label2 + "."

describe "Check client inclusion syntax" do
  before :all do
    # set up: create article, create corresponding item with sitelinks, create properties & add claims
    visit_page(ClientPage) do |page|
      page.create_article(article_title, article_text, true)
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(item_label1, generate_random_string(20))
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(item_label2, generate_random_string(20))
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(article_title, generate_random_string(20))
      page.add_sitelinks(item_sitelinks)
    end
    visit_page(NewPropertyPage) do |page|
      page.create_new_property(item_property_brother, generate_random_string(20), "Item")
      item_property_brother_url = page.current_url
    end
    visit_page(NewPropertyPage) do |page|
      page.create_new_property(item_property_member_of, generate_random_string(20), "Item")
      item_property_member_of_url = page.current_url
    end
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.add_statement(item_property_brother, item_label1)
      page.add_statement(item_property_member_of, item_label2)
    end
  end

  context "Check item-property inclusion syntax" do
    it "should check if item property gets included properly" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.clientArticleTitle.should == article_title
        page.clientArticleText.should == article_text
        page.count_interwiki_links.should == item_sitelinks.count - 1
        page.interwiki_de?.should be_true
        page.change_article(article_title, article_text + article_text_extended)
        page.clientArticleText.should == article_text_valid
      end
    end
  end

  after :all do
    # tear down: remove sitelinks, reset article, delete properties
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.remove_all_sitelinks
    end
    on_page(ClientPage) do |page|
      page.change_article(article_title, article_text)
    end
    visit_page(RepoLoginPage) do |page|
      page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
    end
    visit_page(DeleteEntityPage) do |page|
      page.delete_entity(item_property_brother_url)
    end
    visit_page(DeleteEntityPage) do |page|
      page.delete_entity(item_property_member_of_url)
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end
end
