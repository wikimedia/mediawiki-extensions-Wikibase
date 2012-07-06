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
      visit_page(SitelinksItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
        page.wait_for_sitelinks_to_load

        page.sitelinksTable?.should be_true
        page.addSitelinkLink?.should be_true
        page.siteLinkCounter?.should be_true

        numExistingSitelinks = page.countExistingSitelinks
        numExistingSitelinks.should == 0
        numExistingSitelinks.should == page.getNumberOfSitelinksFromCounter

        page.addSitelinkLink
        page.siteIdInputField_element.should be_true
        page.pageInputField.should be_true
        page.saveSitelinkLinkDisabled.should be_true
        page.cancelSitelinkLink?.should be_true
        page.cancelSitelinkLink

        @browser.refresh
        page.wait_for_sitelinks_to_load
        page.countExistingSitelinks.should == 0
      end
    end
  end

  context "Check for adding site link to non existing article" do
    it "should check if adding sitelink to a non existing article produces an error" do
      on_page(SitelinksItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.countExistingSitelinks.should == 0
        page.addSitelinkLink
        page.siteIdInputField_element.should be_true
        page.pageInputField_element.enabled?.should be_false
        page.siteIdInputField="en"
        ajax_wait
        page.wait_until do
          page.siteIdAutocompleteList_element.visible?
        end
        page.siteIdInputField_element.send_keys :arrow_down

        page.siteIdAutocompleteList_element.visible?.should be_true
        aCListElement = page.getNthElementInAutocompleteList(page.siteIdAutocompleteList_element, 1)
        aCListElement.visible?.should be_true
        aCListElement.click

        page.pageInputField_element.enabled?.should be_true
        page.pageInputField="xyz_thisarticleshouldneverexist_xyz"
        ajax_wait
        if page.saveSitelinkLink?
          page.saveSitelinkLink
        end
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
      on_page(SitelinksItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.countExistingSitelinks.should == 0
        page.addSitelinkLink
        page.siteIdInputField_element.should be_true
        page.pageInputField_element.enabled?.should be_false
        page.siteIdInputField="en"
        ajax_wait
        page.wait_until do
          page.siteIdAutocompleteList_element.visible?
        end
        page.siteIdInputField_element.send_keys :arrow_down

        page.siteIdAutocompleteList_element.visible?.should be_true
        aCListElement = page.getNthElementInAutocompleteList(page.siteIdAutocompleteList_element, 1)
        aCListElement.visible?.should be_true
        aCListElement.click

        page.pageInputField_element.enabled?.should be_true
        page.pageInputField="Ber"
        ajax_wait
        page.wait_until do
          page.pageAutocompleteList_element.visible?
        end

        page.pageInputField_element.send_keys :arrow_down
        page.pageInputField_element.send_keys :return
        #check if the enter-key was recognized; if not then click the save-link (issue in chrome & IE)
        if page.saveSitelinkLink?
          page.saveSitelinkLink
        end
        ajax_wait
        page.wait_for_api_callback

        @browser.refresh
        page.wait_for_sitelinks_to_load
        numExistingSitelinks = page.countExistingSitelinks
        numExistingSitelinks.should == 1
      end
    end
  end

  context "Check for adding multiple site links UI" do
    it "should check if adding multiple sitelinks works" do
      count = 1
      sitelinks = [["de", "Ber"], ["ja", "Ber"], ["he", "Ber"]]
      on_page(SitelinksItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        sitelinks.each do |sitelink|
          page.countExistingSitelinks.should == count
          page.addSitelinkLink
          page.siteIdInputField = sitelink[0]
          ajax_wait
          page.wait_until do
            page.siteIdAutocompleteList_element.visible?
          end
          page.siteIdInputField_element.send_keys :arrow_down

          page.siteIdAutocompleteList_element.visible?.should be_true
          aCListElement = page.getNthElementInAutocompleteList(page.siteIdAutocompleteList_element, 1)
          aCListElement.visible?.should be_true
          aCListElement.click

          page.pageInputField_element.enabled?.should be_true
          page.pageInputField = sitelink[1]
          ajax_wait
          page.wait_until do
            page.pageAutocompleteList_element.visible?
          end

          page.pageInputField_element.send_keys :arrow_down
          page.pageInputField_element.send_keys :return
          if page.saveSitelinkLink?
            page.saveSitelinkLink
          end
          ajax_wait
          page.wait_for_api_callback

          if count == 1
            page.getNthSitelinksTableRow(2).click
            page.wait_until do
              page.editSitelinkLink_element.visible?
            end
            page.editSitelinkLink
            page.siteIdInputField?.should be_false
          end

          @browser.refresh
          page.wait_for_sitelinks_to_load

          count = count+1
          page.getNumberOfSitelinksFromCounter.should == count
        end
        page.countExistingSitelinks.should == count
      end
    end
  end

  context "Check for displaying normalized title when adding sitelink" do
    it "should check if the normalized version of the title is displayed" do
      on_page(SitelinksItemPage) do |page|
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
        aCListElement = page.getNthElementInAutocompleteList(page.siteIdAutocompleteList_element, 1)
        aCListElement.visible?.should be_true
        aCListElement.click

        page.pageInputField_element.enabled?.should be_true
        page.pageInputField = "s"
        ajax_wait
        page.wait_until do
          page.pageAutocompleteList_element.visible?
        end

        if page.saveSitelinkLink?
          page.saveSitelinkLink
        end
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
      on_page(SitelinksItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        page.getNthSitelinksTableRow(2).click
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
        page.getNthSitelinksTableRow(2).click
        page.editSitelinkLink
        page.pageInputField.should_not == current_page
      end
    end
  end

  context "Check for removing multiple site link UI" do
    it "should check if removing multiple sitelink works" do
      on_page(SitelinksItemPage) do |page|
        page.navigate_to_item
        page.wait_for_sitelinks_to_load
        numExistingSitelinks = page.countExistingSitelinks
        page.getNthSitelinksTableRow(1).click
        page.removeSitelinkLink?.should be_true
        for i in 1..numExistingSitelinks
          page.getNthSitelinksTableRow(1).click
          page.removeSitelinkLink?.should be_true
          page.removeSitelinkLink
          ajax_wait
          page.wait_for_api_callback

          @browser.refresh
          page.wait_for_sitelinks_to_load
          page.countExistingSitelinks.should == (numExistingSitelinks-i)
        end
        @browser.refresh
        page.wait_for_sitelinks_to_load
        page.countExistingSitelinks.should == 0
      end
    end
  end
end

