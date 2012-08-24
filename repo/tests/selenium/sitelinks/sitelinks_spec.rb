# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for sitelinks

require 'spec_helper'

describe "Check functionality of add/edit/remove sitelinks" do

  context "Check for empty site links UI" do
    it "should check that there are no site links and if there's an add button" do
      visit_page(ItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
        page.wait_for_sitelinks_to_load

        page.sitelinksTable?.should be_true
        page.addSitelinkLink?.should be_true
        page.siteLinkCounter?.should be_true

        numExistingSitelinks = page.count_existing_sitelinks
        numExistingSitelinks.should == 0
        numExistingSitelinks.should == page.get_number_of_sitelinks_from_counter

        page.addSitelinkLink
        page.siteIdInputField_element.should be_true
        page.pageInputField.should be_true
        page.saveSitelinkLinkDisabled.should be_true
        page.cancelSitelinkLink?.should be_true
        page.cancelSitelinkLink

        page.count_existing_sitelinks.should == 0
        @browser.refresh
        page.wait_for_sitelinks_to_load
        page.count_existing_sitelinks.should == 0
      end
    end
  end

  context "Check for adding site link to non existing article" do
    it "should check if adding sitelink to a non existing article produces an error" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.count_existing_sitelinks.should == 0
        page.addSitelinkLink
        page.siteIdInputField_element.should be_true
        page.pageInputField_element.enabled?.should be_false
        page.siteIdInputField="en"
        ajax_wait
        page.wait_until do
          page.siteIdAutocompleteList_element.visible?
        end
        page.siteIdAutocompleteList_element.visible?.should be_true

        page.pageInputField_element.enabled?.should be_true
        page.pageInputField="xyz_thisarticleshouldneverexist_xyz"
        page.siteIdInputField.should == "English (en)"
        ajax_wait
        page.saveSitelinkLink
        ajax_wait
        page.wait_for_api_callback

        page.wbErrorDiv?.should be_true
        page.wbErrorDetailsLink?.should be_true
        page.wbErrorDetailsLink
        page.wbErrorDetailsDiv?.should be_true
        page.wbErrorDetailsDiv_element.text.should == "The external client site did not provide page information."
      end
    end
  end

  context "Check for adding site link UI" do
    it "should check if adding a sitelink works" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.count_existing_sitelinks.should == 0
        page.addSitelinkLink
        page.siteIdInputField_element.should be_true
        page.pageInputField_element.enabled?.should be_false
        page.siteIdInputField="en"
        ajax_wait
        page.wait_until do
          page.siteIdAutocompleteList_element.visible?
        end
        page.siteIdAutocompleteList_element.visible?.should be_true

        page.pageInputField_element.enabled?.should be_true
        page.pageInputField="Ber"
        page.siteIdInputField.should == "English (en)"
        ajax_wait
        page.wait_until do
          page.pageAutocompleteList_element.visible?
        end
        page.saveSitelinkLink
        ajax_wait
        page.wait_for_api_callback
        sleep 1
        # let's check if we are not allowed to change the siteId when editing
        @browser.refresh
        page.wait_for_sitelinks_to_load
        page.editSitelinkLink
        page.siteIdInputField?.should be_false
        page.pageInputField?.should be_true
        page.cancelSitelinkLink

        numExistingSitelinks = page.count_existing_sitelinks
        numExistingSitelinks.should == 1
        @browser.refresh
        page.wait_for_sitelinks_to_load
        numExistingSitelinks = page.count_existing_sitelinks
        numExistingSitelinks.should == 1
      end
    end
  end

  context "Check for adding multiple site links UI" do
    it "should check if adding multiple sitelinks works" do
      count = 1
      sitelinks = [["de", "Ber", "Deutsch (de)"], ["ja", "Ber", "日本語 (ja)"], ["he", "Ber", "עברית (he)"]]
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        sitelinks.each do |sitelink|
          page.count_existing_sitelinks.should == count
          page.addSitelinkLink
          page.siteIdInputField = sitelink[0]
          ajax_wait
          page.wait_until do
            page.siteIdAutocompleteList_element.visible?
          end
          page.siteIdAutocompleteList_element.visible?.should be_true

          page.pageInputField_element.enabled?.should be_true
          page.pageInputField = sitelink[1]
          page.siteIdInputField.should == sitelink[2]
          ajax_wait
          page.wait_until do
            page.pageAutocompleteList_element.visible?
          end
          page.saveSitelinkLink
          ajax_wait
          page.wait_for_api_callback
          sleep 1
          count = count+1
          @browser.refresh
          page.wait_for_sitelinks_to_load
        end
      end
    end
  end

  context "Check for displaying normalized title when adding sitelink" do
    it "should check if the normalized version of the title is displayed" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.addSitelinkLink
        page.siteIdInputField = "sr"
        ajax_wait
        page.wait_until do
          page.siteIdAutocompleteList_element.visible?
        end
        page.siteIdInputField_element.send_keys :arrow_down

        page.siteIdAutocompleteList_element.visible?.should be_true
        aCListElement = page.get_nth_element_in_autocomplete_list(page.siteIdAutocompleteList_element, 1)
        aCListElement.visible?.should be_true
        aCListElement.click

        page.pageInputField_element.enabled?.should be_true
        page.pageInputField = "s"
        ajax_wait
        page.wait_until do
          page.pageAutocompleteList_element.visible?
        end
        page.saveSitelinkLink
        ajax_wait
        page.wait_for_api_callback
        sleep 1 #cause there's a delay before the value is actually set in the dom -> should be changed in the UI
        page.pageArticleNormalized?.should be_true
        page.pageArticleNormalized_element.text.should == "С"

        @browser.refresh
        page.wait_for_sitelinks_to_load
        page.pageArticleNormalized_element.text.should == "С"
        page.pageArticleNormalized?.should be_true
      end
    end
  end

  context "Check for editing site links UI" do
    it "should check if editing sitelinks works" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.editSitelinkLink
        page.saveSitelinkLinkDisabled?.should be_true
        page.cancelSitelinkLink?.should be_true
        page.pageInputField_element.enabled?.should be_true
        current_page = page.pageInputField
        new_page = "Ber"
        page.pageInputField = new_page
        ajax_wait
        page.wait_until do
          page.editSitelinkAutocompleteList_element.visible?
        end
        page.pageInputField_element.send_keys :arrow_down
        page.pageInputField_element.send_keys :arrow_down
        page.pageInputField_element.send_keys :return
        if page.saveSitelinkLink?
          page.saveSitelinkLink
        end
        ajax_wait
        page.wait_for_api_callback

        @browser.refresh
        page.wait_for_sitelinks_to_load
        page.editSitelinkLink
        page.pageInputField.should_not == current_page
      end
    end
  end

  context "Check clicking on sitelink" do
    it "should check if the sitelink leads to the correct page" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.germanSitelink
        page.articleTitle.should == "Berlin"
      end
    end
  end

  context "Check for removing multiple site link UI" do
    it "should check if removing multiple sitelink works" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        numExistingSitelinks = page.count_existing_sitelinks
        page.removeSitelinkLink?.should be_true
        for i in 1..numExistingSitelinks
          page.removeSitelinkLink?.should be_true
          page.removeSitelinkLink
          ajax_wait
          page.wait_for_api_callback

          @browser.refresh
          page.wait_for_sitelinks_to_load
          page.count_existing_sitelinks.should == (numExistingSitelinks-i)
        end
        @browser.refresh
        page.wait_for_sitelinks_to_load
        page.count_existing_sitelinks.should == 0
      end
    end
  end
end

