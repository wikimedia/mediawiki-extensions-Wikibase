# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for the STTL language switcher

require 'spec_helper'

label_en = "english_" + generate_random_string(5)
description_en = "english_" + generate_random_string(10)
label_de = "deutsch_" + generate_random_string(5)
description_de = "deutsch_" + generate_random_string(10)

describe "Check functionality of STTL language switcher" do
  context "STTL test setup" do
    it "should create item, enter label and description in english and german" do
      visit_page(NewItemPage) do |page|
        page.create_new_item(label_en, description_en)
      end
      on_page(LanguageSelectorPage) do |page|
        page.sttlLinkDe
        page.wait_for_item_to_load
        page.editLabelLink
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

  context "STTL test language switcher behaviour" do
    it "should check if seleced language gets removed from language selector list" do
      on_page(LanguageSelectorPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.sttlLinkEn?.should be_false
        page.sttlLinkDe?.should be_true
        page.sttlLinkDe
        page.wait_for_item_to_load
        page.sttlLinkDe?.should be_false
        page.sttlLinkEn?.should be_true
        page.sttlLinkEn
        page.sttlLinkEn?.should be_false
        page.sttlLinkDe?.should be_true
      end
    end
  end

  context "STTL test language switching for item" do
    it "should check if language switching works for item page" do
      on_page(LanguageSelectorPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label_en
        page.itemDescriptionSpan.should == description_en
        page.sttlLinkDe
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label_de
      end
    end
  end

  context "STTL test language stickiness" do
    it "should check if language is sticky when navigating to other pages" do
      on_page(LanguageSelectorPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label_en
        page.viewTabLink_element.text.should == "Read"
        page.recentChangesLink
        page.specialPageTabLink_element.text.should == "Special page"
        page.sttlLinkDe
        page.specialPageTabLink_element.text.should == "Spezialseite"
        page.firstResultLink
        page.wait_for_item_to_load
        page.viewTabLink_element.text.should == "Lesen"
        page.itemLabelSpan.should == label_de
      end
    end
  end

#   context "STTL test more languages expansion" do
#     it "should check if more languages expand" do
#       on_page(LanguageSelectorPage) do |page|
#         page.navigate_to_item
#         page.wait_for_item_to_load
#         page.sttlLiTo_element.parent.style("display").should == "none"
#         page.moreLanguagesLink
#         page.sttlLiTo_element.parent.style("display").should_not == "none"
#         page.moreLanguagesLink
#         sleep 1 # wait for fold animation to finish
#         page.sttlLiTo_element.parent.style("display").should == "none"
#       end
#     end
#   end
end
