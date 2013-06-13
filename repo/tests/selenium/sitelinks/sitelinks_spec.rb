# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for sitelinks

require 'spec_helper'

describe "Check functionality of add/edit/remove sitelinks" do
  before :all do
    # set up
    visit_page(CreateItemPage) do |page|
      page.create_new_item(generate_random_string(10), generate_random_string(20))
    end
  end
  context "Check sitelinks UI" do
    it "should check that there are no site links and if there's an add button" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
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
        page.wait_for_entity_to_load
        page.count_existing_sitelinks.should == 0
      end
    end

    it "should check if adding sitelink to a non existing article produces an error" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
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

    it "should check if an error is correctly displayed when the site API is inaccessible" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.count_existing_sitelinks.should == 0
        page.addSitelinkLink
        page.siteIdInputField_element.should be_true
        page.pageInputField_element.enabled?.should be_false
        page.siteIdInputField="English (en)"
        ajax_wait
        page.wait_until do
          page.siteIdAutocompleteList_element.visible?
        end
        page.siteIdAutocompleteList_element.visible?.should be_true
        page.pageInputField_element.enabled?.should be_true
        @browser.execute_script("$( '.wb-sitelinks-link input.ui-suggester-input' ).data( 'suggester' ).options.ajax.url = 'thisWillCauseAnError';")
        page.pageInputField="Montreal"
        ajax_wait
        page.wbErrorDiv?.should be_true
        page.wbErrorDetailsLink?.should be_true
        page.wbErrorDetailsLink
        page.wbErrorDetailsDiv?.should be_true
      end
    end

    it "should check if adding a sitelink works" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.count_existing_sitelinks.should == 0
        page.addSitelinkLink
        page.siteIdInputField_element.should be_true
        page.pageInputField_element.enabled?.should be_false
        page.siteIdInputField="e" # check if fallback language is selected
        page.wait_until do
          page.siteIdAutocompleteList_element.visible?
        end
        page.pageInputField_element.enabled?.should be_true
        page.siteIdInputField="en-doesnotexist" # check if no fallback language is selected
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
      end
    end
    it "should click on sitelink to check if URL was constructed correctly" do
      on_page(ItemPage) do |page|
        page.englishSitelink?.should be_true
        page.englishSitelink
        page.articleTitle.should == "Berlin"
      end
    end
    it "should check if siteId is not editable while in edit mode" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editSitelinkLink
        page.siteIdInputField?.should be_false
        page.pageInputFieldExistingSiteLink?.should be_true
        page.cancelSitelinkLink
      end
    end

    it "should check if adding multiple sitelinks works" do
      count = 1
      sitelinks = [["de", "Ber", "Deutsch (de)"], ["ja", "Ber", "日本語 (ja)"], ["he", "BER", "עברית (he)"]]
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
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
        end
      end
    end

    it "should check if the normalized version of the title is displayed" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_sitelinks([["српски", "Helijum"]])
        page.pageArticleNormalized?.should be_true
        page.pageArticleNormalized_element.text.should == "Хелијум"
      end
    end

    it "should check if editing sitelinks works" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editSitelinkLink
        page.saveSitelinkLinkDisabled?.should be_true
        page.saveSitelinkLink?.should be_false
        page.cancelSitelinkLink?.should be_true
        page.pageInputFieldExistingSiteLink_element.enabled?.should be_true
        current_page = page.pageInputFieldExistingSiteLink
        new_page = "Bermuda"
        page.pageInputFieldExistingSiteLink = new_page
        ajax_wait
        page.wait_until do
          page.editSitelinkAutocompleteList_element.visible?
        end
        page.saveSitelinkLink
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_entity_to_load
        page.editSitelinkLink
        page.pageInputFieldExistingSiteLink.should_not == current_page
      end
    end

    it "should check if the sitelink leads to the correct page" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.germanSitelink
        page.articleTitle.should == "Bermuda"
      end
    end

    it "should check correct message when maximum number of sitelinks are reached" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addSitelinkLink?.should be_true
        @browser.execute_script("wb.ui.SiteLinksEditTool.prototype.isFull = function() { return true; };")
        page.add_sitelinks([["fr", "Croissant"]])
        page.addSitelinkLink?.should be_false
        @browser.refresh
        page.wait_for_entity_to_load
        page.addSitelinkLink?.should be_true
      end
    end

    it "should check correct sorting of sitelinks" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.get_text_from_sitelist_table(1, 1).should == "dewiki"
        page.get_text_from_sitelist_table(2, 1).should == "enwiki"
        page.get_text_from_sitelist_table(3, 1).should == "frwiki"
        page.get_text_from_sitelist_table(4, 1).should == "hewiki"
        page.get_text_from_sitelist_table(5, 1).should == "jawiki"
        page.get_text_from_sitelist_table(6, 1).should == "srwiki"
        page.sitelinksHeaderLanguage_element.click
        page.get_text_from_sitelist_table(1, 1).should == "dewiki"
        page.get_text_from_sitelist_table(2, 1).should == "enwiki"
        page.get_text_from_sitelist_table(3, 1).should == "frwiki"
        page.get_text_from_sitelist_table(4, 1).should == "srwiki"
        page.get_text_from_sitelist_table(5, 1).should == "hewiki"
        page.get_text_from_sitelist_table(6, 1).should == "jawiki"
        page.sitelinksHeaderCode_element.click
        page.get_text_from_sitelist_table(1, 1).should == "dewiki"
        page.get_text_from_sitelist_table(2, 1).should == "enwiki"
        page.get_text_from_sitelist_table(3, 1).should == "frwiki"
        page.get_text_from_sitelist_table(4, 1).should == "hewiki"
        page.get_text_from_sitelist_table(5, 1).should == "jawiki"
        page.get_text_from_sitelist_table(6, 1).should == "srwiki"
      end
    end

    it "should check if localizing numbers works" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.uls_switch_language("ar", "arabic")
        page.wait_for_entity_to_load
        page.siteLinkCounter.include?("٦").should be_true
      end
    end

    it "should check if removing multiple sitelink works" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.uls_switch_language(LANGUAGE_CODE, LANGUAGE_NAME)
        page.wait_for_entity_to_load
        numExistingSitelinks = page.count_existing_sitelinks
        for i in 1..numExistingSitelinks
          page.editSitelinkLink
          page.removeSitelinkLink?.should be_true
          page.removeSitelinkLink
          ajax_wait
          page.wait_for_api_callback
          page.count_existing_sitelinks.should == (numExistingSitelinks-i)
        end
        @browser.refresh
        page.wait_for_entity_to_load
        page.count_existing_sitelinks.should == 0
      end
    end
  end
  after :all do
    # tear down: remove all sitelinks if there remained some
    on_page(ItemPage) do |page|
      page.remove_all_sitelinks
    end
  end
end

