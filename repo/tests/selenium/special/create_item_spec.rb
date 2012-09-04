# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for CreateItem special page

require 'spec_helper'

describe "Check CreateItem special page" do

  context "create item functionality" do
    it "should create a new item with label and description" do
      label = generate_random_string(10)
      description = generate_random_string(20)
      visit_page(CreateItemPage) do |page|
        page.createItemLabelField = label
        page.createItemDescriptionField = description
        page.createItemSubmit
        page.wait_for_item_to_load
      end
      on_page(ItemPage) do |page|
        page.itemLabelSpan.should == label
        page.itemDescriptionSpan.should == description
      end
    end
    it "should create a new item with label and empty description" do
      label = generate_random_string(10)
      description = generate_random_string(20)
      visit_page(CreateItemPage) do |page|
        page.createItemLabelField = label
        page.createItemSubmit
        page.wait_for_item_to_load
      end
      on_page(ItemPage) do |page|
        page.itemLabelSpan.should == label
        page.descriptionInputField?.should be_true
      end
    end
    it "should create a new item with description and empty label" do
      label = generate_random_string(10)
      description = generate_random_string(20)
      visit_page(CreateItemPage) do |page|
        page.createItemDescriptionField = description
        page.createItemSubmit
        page.wait_for_item_to_load
      end
      on_page(ItemPage) do |page|
        page.itemDescriptionSpan.should == description
        page.labelInputField?.should be_true
      end
    end
  end

end
