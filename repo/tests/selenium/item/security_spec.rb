# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for security issues

require 'spec_helper'

dangerous_text = "<script>$('body').empty();</script>"
template_text = "{{Template:Foo}}"

describe "Check for security issues" do
  before :all do
    # set up
  end
  context "check for JS injection for item labels/descriptions" do
    it "should check if no JS injection is possible for labels" do
      visit_page(CreateItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.change_label(dangerous_text)
        page.entityLabelSpan.should == dangerous_text
        @browser.refresh
        page.firstHeading?.should be_true
      end
    end
    it "should check if no JS injection is possible for descriptions" do
      visit_page(CreateItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.change_description(dangerous_text)
        page.entityDescriptionSpan.should == dangerous_text
        @browser.refresh
        page.firstHeading?.should be_true
      end
    end
  end
  context "check for JS injection for item aliases" do
    it "should check if no JS injection is possible for aliases" do
      visit_page(CreateItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_aliases([dangerous_text])
        @browser.refresh
        page.firstHeading?.should be_true
      end
    end
  end
  context "check for JS injection for property labels/descriptions" do
    it "should check if no JS injection is possible for property labels" do
      visit_page(NewPropertyPage) do |page|
        page.create_new_property(generate_random_string(10), generate_random_string(20))
      end
      on_page(PropertyPage) do |page|
        page.navigate_to_property
        page.wait_for_entity_to_load
        page.change_label(dangerous_text)
        page.entityLabelSpan.should == dangerous_text
        @browser.refresh
        page.firstHeading?.should be_true
        # Reset property label to prevent conflicts in repeated test runs:
        page.change_label(generate_random_string(10))
      end
    end
    it "should check if no JS injection is possible for property descriptions" do
      visit_page(NewPropertyPage) do |page|
        page.create_new_property(generate_random_string(10), generate_random_string(20))
      end
      on_page(PropertyPage) do |page|
        page.navigate_to_property
        page.wait_for_entity_to_load
        page.change_description(dangerous_text)
        page.entityDescriptionSpan.should == dangerous_text
        @browser.refresh
        page.firstHeading?.should be_true
      end
    end
    context "check for JS injection for property aliases" do
      it "should check if no JS injection is possible for aliases" do
        visit_page(NewPropertyPage) do |page|
          page.create_new_property(generate_random_string(10), generate_random_string(20))
        end
        on_page(PropertyPage) do |page|
          page.navigate_to_property
          page.wait_for_entity_to_load
          page.add_aliases([dangerous_text])
          @browser.refresh
          page.firstHeading?.should be_true
        end
      end
    end
  end
  context "Template replacement prevention" do
    it "should check if templates {{...}} are not replaced" do
      visit_page(CreateItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.change_label(template_text)
        page.entityLabelSpan.should == template_text
        @browser.refresh
        page.entityLabelSpan.should == template_text
        @browser.title.include?(template_text).should be_true
      end
    end
  end
  after :all do
    # tear down
  end
end
