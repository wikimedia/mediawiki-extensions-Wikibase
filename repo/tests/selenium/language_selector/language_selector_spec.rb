# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for switching the language using ULS

require 'spec_helper'

label_en = "english_" + generate_random_string(5)
description_en = "english_" + generate_random_string(10)
label_de = "deutsch_" + generate_random_string(5)
description_de = "deutsch_" + generate_random_string(10)

describe "Check functionality of wikidata together with ULS" do
  context "ULS test setup" do
    it "should create item, enter label and description in english and german" do
      visit_page(ItemPage) do |page|
        page.uls_switch_language("English")
        page.wait_for_item_to_load
        page.create_new_item(label_en, description_en)
      end
      on_page(ItemPage) do |page|
        page.uls_switch_language("Deutsch")
        page.wait_for_item_to_load
        page.labelInputField_element.clear
        page.labelInputField = label_de
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.descriptionInputField_element.clear
        page.descriptionInputField = description_de
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
      end
    end
  end

  context "ULS test language switching for item" do
    it "should check if language switching works for item page" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.uls_switch_language("English")
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label_en
        page.itemDescriptionSpan.should == description_en
        page.uls_switch_language("Deutsch")
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label_de
        page.uls_switch_language("English")
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label_en
      end
    end
  end

  # ULS is still buggy and the following test won't work in browsers different from firefox
  if ENV["BROWSER_TYPE"] == "firefox"
    context "ULS test language stickiness" do
      it "should check if language is sticky when navigating to other pages" do
        on_page(ItemPage) do |page|
          page.navigate_to_item
          page.wait_for_item_to_load
          page.uls_switch_language("English")
          page.wait_for_item_to_load
          page.itemLabelSpan.should == label_en
          page.viewTabLink_element.text.should == "Read"
          page.recentChangesLink
          page.specialPageTabLink_element.text.should == "Special page"
          page.uls_switch_language("Deutsch")
          page.specialPageTabLink_element.text.should == "Spezialseite"
          page.firstResultLink
          page.wait_for_item_to_load
          page.viewTabLink_element.text.should == "Lesen"
          page.itemLabelSpan.should == label_de
          page.uls_switch_language("English")
          page.wait_for_item_to_load
          page.itemLabelSpan.should == label_en
        end
      end
    end
  end
end
