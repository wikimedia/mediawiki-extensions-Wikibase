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
  context "startup" do
    it "just some simple startup checks" do
      # create new item
      visit_page(AliasesItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load

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
      on_page(AliasesItemPage) do |page|
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases
        page.addAliasesDivInEditMode_element.style("display").should == "none"
        page.cancelAliases?.should be_true
        page.cancelAliases
        page.addAliases?.should be_true
        page.cancelAliases?.should be_false
        page.addAliases
        page.addAliasesDivInEditMode_element.style("display").should == "none"
        page.cancelAliases?.should be_true
        page.cancelAliases
      end
    end
  end
end

