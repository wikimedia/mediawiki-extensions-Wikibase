# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for ItemDisambiguation special page

require 'spec_helper'

label_abc = generate_random_string(10)
label_ac_in_de = generate_random_string(10)
description_a = generate_random_string(20)
description_b = generate_random_string(20)

describe "Check item disambiguation special page" do
  before :all do
    # set up: create 3 items with same label but with two different descriptions and one without
    visit_page(CreateItemPage) do |page|
      page.uls_switch_language("en", "english")
      page.create_new_item(label_abc, description_a,false)
      page.wait_for_entity_to_load
      page.uls_switch_language("de", "deutsch")
      page.wait_for_entity_to_load
      page.change_label(label_ac_in_de)
      page.uls_switch_language("en", "english")
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label_abc, description_b, false)
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label_abc, "", false)
      page.wait_for_entity_to_load
      page.uls_switch_language("de", "deutsch")
      page.wait_for_entity_to_load
      page.change_label(label_ac_in_de)
      page.uls_switch_language("en", "english")
    end
  end

  context "item disambiguation functionality test" do
    it "language field should be preset with the language" do
      visit_page(ItemDisambiguationPage) do |page|
        page.uls_switch_language("de", "deutsch")
        page.disambiguationLanguageField.should == "de"
        page.uls_switch_language("en", "english")
        page.disambiguationLanguageField.should == "en"
      end
    end
    it "should search for item by label" do
      visit_page(ItemDisambiguationPage) do |page|
        page.disambiguationLanguageField = "en"
        page.disambiguationLabelField = label_abc
        page.disambiguationSubmit
        page.countDisambiguationElements.should == 3
        page.disambiguationItemLink1_element.click
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_abc
        page.entityDescriptionSpan.should == description_a
        @browser.back
        page.wait_until do
          page.disambiguationItemLink1?
        end
        page.disambiguationItemLink2_element.click
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_abc
        page.entityDescriptionSpan.should == description_b
        @browser.back
        page.wait_until do
          page.disambiguationItemLink1?
        end
        page.disambiguationItemLink3_element.click
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_abc
      end
    end
    it "should search for item by label in other language" do
      visit_page(ItemDisambiguationPage) do |page|
        page.disambiguationLanguageField = "de"
        page.disambiguationLabelField = label_ac_in_de
        page.disambiguationSubmit
        page.wait_until do
          page.disambiguationItemLink1?
        end
        page.countDisambiguationElements.should == 2
        page.disambiguationItemLink1_element.click
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_abc
        page.entityDescriptionSpan.should == description_a
        @browser.back
        page.wait_until do
          page.disambiguationItemLink1?
        end
        page.disambiguationItemLink2_element.click
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_abc
      end
    end
  end
  after :all do
    # tear down
  end
end
