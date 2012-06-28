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
      # visit_page(LoginPage)
      # @current_page.login_with(WIKI_USERNAME, WIKI_PASSWORD)

      visit_page(SitelinksItemPage)
      @current_page.create_new_item(generate_random_string(10), generate_random_string(20))
      @current_page.wait_for_sitelinks_to_load

      @current_page.sitelinksTable?.should be_true
      @current_page.addSitelinkLink?.should be_true
      @current_page.siteLinkCounter?.should be_true

      numExistingSitelinks = @current_page.countExistingSitelinks
      numExistingSitelinks.should == 0
      numExistingSitelinks.should == @current_page.getNumberOfSitelinksFromCounter

      @current_page.addSitelinkLink
      @current_page.siteIdInputField_element.should be_true
      @current_page.pageInputField.should be_true
      @current_page.saveSitelinkLinkDisabled.should be_true
      @current_page.cancelSitelinkLink?.should be_true
      @current_page.cancelSitelinkLink

      @browser.refresh
      @current_page.wait_for_sitelinks_to_load
      @current_page.countExistingSitelinks.should == 0

    end
  end

  context "Check for adding site link to non existing article" do
    it "should check if adding sitelink to a non existing article produces an error" do
      on_page(SitelinksItemPage)
      @current_page.navigate_to_item
      @current_page.wait_for_sitelinks_to_load
      @current_page.countExistingSitelinks.should == 0
      @current_page.addSitelinkLink
      @current_page.siteIdInputField_element.should be_true
      @current_page.pageInputField_element.enabled?.should be_false
      @current_page.siteIdInputField="en"
      ajax_wait
      @current_page.wait_until do
        @current_page.siteIdAutocompleteList_element.visible?
      end
      @current_page.siteIdInputField_element.send_keys :arrow_down

      @current_page.siteIdAutocompleteList_element.visible?.should be_true
      aCListElement = @current_page.getNthElementInAutocompleteList(@current_page.siteIdAutocompleteList_element, 1)
      aCListElement.visible?.should be_true
      aCListElement.click

      @current_page.pageInputField_element.enabled?.should be_true
      @current_page.pageInputField="xyz_thisarticleshouldneverexist_xyz"
      ajax_wait
      if @current_page.saveSitelinkLink?
        @current_page.saveSitelinkLink
      end
      ajax_wait
      @current_page.wait_for_api_callback

      @current_page.wbErrorDiv?.should be_true
      @current_page.wbErrorDetailsLink?.should be_true
      @current_page.wbErrorDetailsLink
      @current_page.wbErrorDetailsDiv?.should be_true
      @current_page.wbErrorDetailsDiv_element.text.should == "The external client site did not provide page information."
    end
  end

  context "Check for adding site link UI" do
    it "should check if adding a sitelink works" do
      on_page(SitelinksItemPage)
      @current_page.navigate_to_item
      @current_page.wait_for_sitelinks_to_load
      @current_page.countExistingSitelinks.should == 0
      @current_page.addSitelinkLink
      @current_page.siteIdInputField_element.should be_true
      @current_page.pageInputField_element.enabled?.should be_false
      @current_page.siteIdInputField="en"
      ajax_wait
      @current_page.wait_until do
        @current_page.siteIdAutocompleteList_element.visible?
      end
      @current_page.siteIdInputField_element.send_keys :arrow_down

      @current_page.siteIdAutocompleteList_element.visible?.should be_true
      aCListElement = @current_page.getNthElementInAutocompleteList(@current_page.siteIdAutocompleteList_element, 1)
      aCListElement.visible?.should be_true
      aCListElement.click

      @current_page.pageInputField_element.enabled?.should be_true
      @current_page.pageInputField="Ber"
      ajax_wait
      @current_page.wait_until do
        @current_page.pageAutocompleteList_element.visible?
      end

      @current_page.pageInputField_element.send_keys :arrow_down
      @current_page.pageInputField_element.send_keys :return
      #check if the enter-key was recognized; if not then click the save-link (issue in chrome & IE)
      if @current_page.saveSitelinkLink?
        @current_page.saveSitelinkLink
      end
      ajax_wait
      @current_page.wait_for_api_callback

      @browser.refresh
      @current_page.wait_for_sitelinks_to_load
      numExistingSitelinks = @current_page.countExistingSitelinks
      numExistingSitelinks.should == 1
    end
  end

  context "Check for adding multiple site links UI" do
    it "should check if adding multiple sitelinks works" do
      count = 1
      sitelinks = [["de", "Ber"], ["ja", "Ber"], ["he", "Ber"]]
      on_page(SitelinksItemPage)
      @current_page.navigate_to_item
      @current_page.wait_for_sitelinks_to_load
      sitelinks.each do |sitelink|
        @current_page.countExistingSitelinks.should == count
        @current_page.addSitelinkLink
        @current_page.siteIdInputField = sitelink[0]
        ajax_wait
        @current_page.wait_until do
          @current_page.siteIdAutocompleteList_element.visible?
        end
        @current_page.siteIdInputField_element.send_keys :arrow_down

        @current_page.siteIdAutocompleteList_element.visible?.should be_true
        aCListElement = @current_page.getNthElementInAutocompleteList(@current_page.siteIdAutocompleteList_element, 1)
        aCListElement.visible?.should be_true
        aCListElement.click

        @current_page.pageInputField_element.enabled?.should be_true
        @current_page.pageInputField = sitelink[1]
        ajax_wait
        @current_page.wait_until do
          @current_page.pageAutocompleteList_element.visible?
        end

        @current_page.pageInputField_element.send_keys :arrow_down
        @current_page.pageInputField_element.send_keys :return
        if @current_page.saveSitelinkLink?
          @current_page.saveSitelinkLink
        end
        ajax_wait
        @current_page.wait_for_api_callback
        @browser.refresh
        @current_page.wait_for_sitelinks_to_load

        count = count+1
        @current_page.getNumberOfSitelinksFromCounter.should == count
      end
      @current_page.countExistingSitelinks.should == count
    end
  end

  context "Check for displaying normalized title when adding sitelink" do
    it "should check if the normalized version of the title is displayed" do
      on_page(SitelinksItemPage)
      @current_page.navigate_to_item
      @current_page.wait_for_sitelinks_to_load
      @current_page.addSitelinkLink
      @current_page.siteIdInputField = "sr"
      ajax_wait
      @current_page.wait_until do
        @current_page.siteIdAutocompleteList_element.visible?
      end
      @current_page.siteIdInputField_element.send_keys :arrow_down

      @current_page.siteIdAutocompleteList_element.visible?.should be_true
      aCListElement = @current_page.getNthElementInAutocompleteList(@current_page.siteIdAutocompleteList_element, 1)
      aCListElement.visible?.should be_true
      aCListElement.click

      @current_page.pageInputField_element.enabled?.should be_true
      @current_page.pageInputField = "s"
      ajax_wait
      @current_page.wait_until do
        @current_page.pageAutocompleteList_element.visible?
      end

      if @current_page.saveSitelinkLink?
        @current_page.saveSitelinkLink
      end
      ajax_wait
      @current_page.wait_for_api_callback
      sleep 1 #cause there's a delay before the value is actually set in the dom -> should be changed in the UI
      @current_page.pageArticleNormalized?.should be_true
      @current_page.pageArticleNormalized_element.text.should == "\u0421"

      @browser.refresh
      @current_page.wait_for_sitelinks_to_load
      @current_page.pageArticleNormalized_element.text.should == "\u0421"
      @current_page.pageArticleNormalized?.should be_true
    end
  end

  context "Check for editing site links UI" do
    it "should check if editing sitelinks works" do
      on_page(SitelinksItemPage)
      @current_page.navigate_to_item
      @current_page.wait_for_sitelinks_to_load
      @current_page.getNthSitelinksTableRow(2).click
      @current_page.editSitelinkLink
      @current_page.saveSitelinkLinkDisabled?.should be_true
      @current_page.cancelSitelinkLink?.should be_true
      @current_page.pageInputField_element.enabled?.should be_true
      current_page = @current_page.pageInputField
      new_page = "Ber"
      @current_page.pageInputField = new_page
      ajax_wait
      @current_page.wait_until do
        @current_page.editSitelinkAutocompleteList_element.visible?
      end
      #TODO: it seems that in the test the sitelink is also saved when the page has not changed
=begin
      @current_page.pageInputField_element.send_keys :arrow_down
      @current_page.pageInputField_element.send_keys :return
      ajax_wait

      @current_page.saveSitelinkLinkDisabled?.should be_true
      sleep 1
      @current_page.pageInputField?.should be_true
      @current_page.pageInputField = new_page
      ajax_wait
      @current_page.wait_until do
        @current_page.editSitelinkAutocompleteList_element.visible?
      end
=end
      @current_page.pageInputField_element.send_keys :arrow_down
      @current_page.pageInputField_element.send_keys :arrow_down
      @current_page.pageInputField_element.send_keys :return
      if @current_page.saveSitelinkLink?
        @current_page.saveSitelinkLink
      end
      ajax_wait
      @current_page.wait_for_api_callback

      @browser.refresh
      @current_page.wait_for_sitelinks_to_load
      @current_page.getNthSitelinksTableRow(2).click
      @current_page.editSitelinkLink
      @current_page.pageInputField.should_not == current_page
    end
  end

  context "Check for removing multiple site link UI" do
    it "should check if removing multiple sitelink works" do
      on_page(SitelinksItemPage)
      @current_page.navigate_to_item
      @current_page.wait_for_sitelinks_to_load
      numExistingSitelinks = @current_page.countExistingSitelinks
      @current_page.getNthSitelinksTableRow(1).click
      @current_page.removeSitelinkLink?.should be_true
      for i in 1..numExistingSitelinks
        @current_page.getNthSitelinksTableRow(1).click
        @current_page.removeSitelinkLink?.should be_true
        @current_page.removeSitelinkLink
        ajax_wait
        @current_page.wait_for_api_callback

        @browser.refresh
        @current_page.wait_for_sitelinks_to_load
        @current_page.countExistingSitelinks.should == (numExistingSitelinks-i)
      end
      @browser.refresh
      @current_page.wait_for_sitelinks_to_load
      @current_page.countExistingSitelinks.should == 0
    end
  end

end

