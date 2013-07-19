# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for old revisions view

require 'spec_helper'

num_items = 1
num_props_string = 1
template_text = "{{Template:" + generate_random_string(10) + "}}"

# items
count = 0
items = Array.new
while count < num_items do
  items.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20)})
  count = count + 1
end

# string properties
count = 0
properties_string = Array.new
string_values = Array.new
while count < num_props_string do
  properties_string.push({"label"=>generate_random_string(10), "description"=>generate_random_string(20), "datatype"=>"String"})
  string_values.push({"value"=>generate_random_string(10), "changed_value"=>generate_random_string(10)})
  count = count + 1
end

describe "Check revisions view" do
  before :all do
    # set up: create items & properties
    items.each do |item|
      visit_page(CreateItemPage) do |page|
        item['id'] = page.create_new_item(item['label'], item['description'])
        item['url'] = page.current_url
      end
    end
    properties_string.each do |property|
      visit_page(NewPropertyPage) do |page|
        property['id'] = page.create_new_property(property['label'], property['description'], property['datatype'])
        property['url'] = page.current_url
      end
    end
    on_page(ItemPage) do |page|
      page.navigate_to items[0]["url"]
      page.wait_for_entity_to_load
      page.change_label(template_text)
      page.change_description(generate_random_string(20))
      page.add_statement(properties_string[0]["label"], string_values[0]["value"])
    end
  end

  context "edit should be disabled" do
    it "should check there are no editbuttons" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.mwFirstHeading.include?(template_text).should === true
        page.oldrevision2
      end
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.editAliases?.should be_false
        page.addAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.addStatement?.should be_false
        page.addClaimToFirstStatement?.should be_false
      end
    end
  end

  context "correct revision should be shown" do
    it "should check correct revision in old-revision-view" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.oldrevision3
      end
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.statement1Name?.should be_false
        page.statement1ClaimValue1?.should be_false
      end
    end
    it "should check correct revision in diff-next-view" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.oldrevision3
        url = page.current_url
        new_url = url + "&diff=next"
        page.navigate_to new_url
      end
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.statement1Name?.should be_false
        page.statement1ClaimValue1?.should be_false
      end
    end
    it "should check correct revision in diff-view" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.select_oldrevision3oldidradio
        page.select_oldrevision2diffradio
        page.comparerevisions
      end
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.statement1Name?.should be_false
        page.statement1ClaimValue1?.should be_false
      end
    end
  end

  after :all do
    # tear down
  end
end
