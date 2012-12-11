# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for the non existing item page

require 'spec_helper'

describe "Check functionality of non existing item page" do
  before :all do
    # set up: switch language
    visit_page(CreateItemPage) do |page|
      page.uls_switch_language(LANGUAGE_CODE, LANGUAGE_NAME)
    end
  end
  context "Check functionality of non existing item page" do
    it "should check for link to Special:CreateItem and firstHeading" do
      visit_page(NonExistingItemPage) do |page|
        page.firstHeading.should be_true
        page.firstHeading_element.text.should == ITEM_NAMESPACE + ITEM_ID_PREFIX + "xy"
        page.specialCreateNewItemLink?.should be_true
        page.specialCreateNewItemLink
      end
      on_page(CreateItemPage) do |page|
        page.createEntityLabelField.should be_true
        page.createEntityDescriptionField.should be_true
      end
    end
  end
end
