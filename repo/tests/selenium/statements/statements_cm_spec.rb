# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for commons media type statements

require 'spec_helper'

item_label = generate_random_string(10)
item_description = generate_random_string(20)
prop_label = generate_random_string(10)
prop_description = generate_random_string(20)
prop_datatype = "Commons media file"
cm_filename = "Air_France_A380_F-HPJA.jpg"
cm_filename_expected = "Air France A380 F-HPJA.jpg"

describe "Check commons media statements UI" do
  before :all do
    # set up: create item & properties
    visit_page(CreateItemPage) do |page|
      page.create_new_item(item_label, item_description)
    end
    visit_page(NewPropertyPage) do |page|
      page.create_new_property(prop_label, prop_description, prop_datatype)
    end
  end

  context "Check statements UI" do
    it "should check adding a statement of commons media type" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_statement(prop_label, cm_filename)
        page.statement1Name.should == prop_label
        page.statement1ClaimValue1.include?(cm_filename_expected).should be_true
        page.statement1ClaimValue1_element.click
        page.current_url.include?("commons.wikimedia.org/wiki/File:" + cm_filename).should be_true
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.statement1Name.should == prop_label
        page.statement1ClaimValue1.include?(cm_filename_expected).should be_true
        page.statement1ClaimValue1_element.click
        page.current_url.include?("commons.wikimedia.org/wiki/File:" + cm_filename).should be_true
      end
    end
  end

  after :all do
    # tear down
  end

end