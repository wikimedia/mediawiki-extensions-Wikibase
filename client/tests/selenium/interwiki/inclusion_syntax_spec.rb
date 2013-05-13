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
items = Array.new
items.push({"label"=>"Barry Gibb", "description"=>generate_random_string(20)})
items.push({"label"=>"Robin Gibb", "description"=>generate_random_string(20)})
items.push({"label"=>"Maurice Gibb", "description"=>generate_random_string(20)})
items.push({"label"=>"Bee Gees", "description"=>generate_random_string(20)})

item_properties = Array.new
item_properties.push({"label"=>"brother", "description"=>"item1 is brother of item2", "datatype"=>"Item"})
item_properties.push({"label"=>"member of", "description"=>"item1 is member of item2", "datatype"=>"Item"})

include_property_brother = "{{#property:" + item_properties[0]["label"] + "}}"
include_property_member_of = "{{#property:" + item_properties[1]["label"] + "}}"
article_text_inclusion = " Together with his brother " + include_property_brother + " he founded the band " + include_property_member_of + "."
article_text_valid_single = "Member of the Bee Gees. Together with his brother " + items[1]["label"] + " he founded the band " + items[3]["label"] + "."
article_text_valid_multi = "Member of the Bee Gees. Together with his brother " + items[1]["label"] + ", " + items[2]["label"] + " he founded the band " + items[3]["label"] + "."

describe "Check client inclusion syntax" do
  before :all do
    # set up: create article, create corresponding item with sitelinks, create properties & add claims
    visit_page(ClientPage) do |page|
      page.create_article(article_title, article_text, true)
    end

    items.each do |item|
      visit_page(CreateItemPage) do |page|
        item['id'] = page.create_new_item(item['label'], item['description'])
        item['url'] = page.current_url
      end
    end

    item_properties.each do |property|
      visit_page(NewPropertyPage) do |page|
        property['id'] = page.create_new_property(property['label'], property['description'], property['datatype'])
        property['url'] = page.current_url
      end
    end

    on_page(ItemPage) do |page|
      page.navigate_to items[0]["url"]
      page.wait_for_entity_to_load
      page.add_sitelinks(item_sitelinks)
      page.add_statement(item_properties[0]["label"], items[1]["label"])
      page.add_statement(item_properties[1]["label"], items[3]["label"])
    end
  end

  context "Check item-property inclusion syntax" do
    it "should check if item-property gets included properly: single value, referenced by property label" do
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        page.clientArticleTitle.should == article_title
        page.clientArticleText.should == article_text
        page.count_interwiki_links.should == item_sitelinks.count - 1
        page.interwiki_de?.should be_true
        page.change_article(article_title, article_text + article_text_inclusion)
        page.clientArticleText.should == article_text_valid_single
      end
    end

    it "should check if item property gets included properly: multiple values, referenced by property id" do
      on_page(ItemPage) do |page|
        page.navigate_to items[0]["url"]
        page.wait_for_entity_to_load
        page.add_statement(item_properties[0]["label"], items[2]["label"])
      end
      on_page(ClientPage) do |page|
        page.navigate_to_article(article_title)
        article_text_inclusion = " Together with his brother " + "{{#property:" + PROPERTY_ID_PREFIX + item_properties[0]["id"] + "}}" + " he founded the band " + "{{#property:" + PROPERTY_ID_PREFIX + item_properties[1]["id"] + "}}" + "."
        page.change_article(article_title, article_text + article_text_inclusion)
        page.clientArticleText.should == article_text_valid_multi
      end
    end
  end

  after :all do
    # tear down: remove sitelinks, reset article, delete properties
    on_page(ItemPage) do |page|
      page.navigate_to items[0]["url"]
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
      page.delete_entity(item_properties[0]["url"])
    end
    visit_page(DeleteEntityPage) do |page|
      page.delete_entity(item_properties[1]["url"])
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end
end
