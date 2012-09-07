# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for item: check edit-tab

require 'spec_helper'

describe "Check functionality of edit-tab" do
  before :all do
    # set up
    visit_page(CreateItemPage) do |page|
      page.create_new_item(generate_random_string(10), generate_random_string(20))
    end
  end

  context "Check if edit-tab is hidden when showing an item" do
    it "should check that the edit-tab is not shown on the item page" do
      on_page(ItemPage) do |page|
        page.editTab?.should be_false
      end
    end
  end

  after :all do
    # tear down
  end
end
