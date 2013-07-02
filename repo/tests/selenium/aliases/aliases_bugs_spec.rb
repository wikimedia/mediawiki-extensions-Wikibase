# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# Author:: Jens Ohlig (jens.ohlig@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for bugs concerning aliases

require 'spec_helper'

describe "Check for bugs" do
  before :all do
    # set up
    visit_page(CreateItemPage) do |page|
      page.create_new_item(generate_random_string(10), generate_random_string(20))
    end
  end

  context "startup" do
    it "just some simple startup checks" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        # check for necessary elements
        page.aliasesDiv?.should be_true
        page.aliasesTitle?.should be_true
        page.aliasesList?.should be_false
        page.editAliases?.should be_false
        page.addAliases?.should be_true
      end
    end
  end

  context "bug: add-button appearing when it should not" do
    it "bug: add-button appearing when it should not" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.addAliases
        page.addAliases?.should be_false
        page.cancelAliases?.should be_true
        page.cancelAliases
        page.addAliases?.should be_true
        page.cancelAliases?.should be_false
        page.addAliases
        page.addAliases?.should be_false
        page.cancelAliases?.should be_true
        page.cancelAliases
      end
    end
  end

  context "bug: zombie alias appearing again after being removed (bug 42101)" do
    it "should check that alias does not get re-added when removed" do
      visit_page(CreateItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_aliases(['zombie'])
        @browser.refresh
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == 1
        page.editAliases
        page.aliasesInputFirstRemove
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        page.add_aliases(['12345'])
        page.count_existing_aliases.should == 1
      end
    end
  end

  after :all do
    # tear down
  end
end

