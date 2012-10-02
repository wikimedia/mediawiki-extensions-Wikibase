# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for known bugs

require 'spec_helper'

description_en = "english"
description_de = "deutsch"

describe "Check for known bugs" do
  before :all do
    # set up
    visit_page(CreateItemPage) do |page|
      page.create_new_item(generate_random_string(10), description_en)
    end
  end
  context "description and aliases appear in wrong languages" do
    it "should check if the bug exists" do
      on_page(ItemPage) do |page|
        page.navigate_to_item_en
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_en
        page.navigate_to_item_de
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should_not == description_en
        page.descriptionInputField_element.clear
        page.descriptionInputField = description_de
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.navigate_to_item_en
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_en
        page.navigate_to_item_de
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_de
      end
    end
  end
  after :all do
    # tear down
  end
end
