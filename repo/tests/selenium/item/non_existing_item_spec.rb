# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for the non existing item page

require 'spec_helper'

describe "Check functionality of non existing item page" do
  context "Check functionality of non existing item page" do
    it "should check for link to Special:CreateItem and firstHeading" do
      visit_page(NonExistingItemPage) do |page|
        page.firstHeading.should be_true
        page.firstHeading_element.text.should == "Data:Qxy"
        page.specialCreateNewItemLink?.should be_true
        page.specialCreateNewItemLink
        page.labelInputField.should be_true
        page.descriptionInputField.should be_true
      end
    end
  end
end
