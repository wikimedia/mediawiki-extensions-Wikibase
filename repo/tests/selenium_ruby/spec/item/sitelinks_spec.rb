require 'spec_helper'

describe "Check functionality of add/edit/remove sitelinks" do

  context "Check for site links UI" do
    it "should check for site links" do
      visit_page(SitelinksItemPage)
      @current_page.sitelinksTable?.should be_true
      @current_page.addSitelinkLink?.should be_true
      @current_page.siteLinkCounter?.should be_true
      
      @current_page.countExistingSitelinks.should == 0
      
      @current_page.addSitelinkLink
      @current_page.siteIdInputField.should be_true
      @current_page.pageInputField.should be_true
      @current_page.sitelinksSaveLinkDisabled.should be_true
      @current_page.cancelSitelinkLink?.should be_true

      @current_page.siteIdInputField_element.enabled?.should be_true
      @current_page.pageInputField_element.enabled?.should be_false
      @current_page.siteIdInputField = "e"
      ajax_wait

      # TODO: find solution: key has to be sent to input field first to get the autocomplete list visible to selenium
      @current_page.siteIdInputField_element.send_keys :arrow_down
      @current_page.siteIdAutocompleteList_element.visible?.should be_true

      # num_of_site_id_elements = @current_page.countAutocompleteListElements(@current_page.siteIdAutocompleteList_element)
      # num_of_site_id_elements.should > 0
      @current_page.getNthElementInAutocompleteList(@current_page.siteIdAutocompleteList_element, 1).click

      @current_page.siteIdAutocompleteList_element.visible?.should be_false
      @current_page.pageInputField_element.enabled?.should be_true
      @current_page.pageInputField = "Berli"
      ajax_wait

      # TODO: find solution: key has to be sent to input field first to get the autocomplete list visible to selenium
      @current_page.pageInputField_element.send_keys :arrow_down
      @current_page.pageAutocompleteList_element.visible?.should be_true

      # num_of_site_id_elements = @current_page.countAutocompleteListElements(@current_page.pageAutocompleteList_element)
      # num_of_site_id_elements.should > 0
      @current_page.getNthElementInAutocompleteList(@current_page.pageAutocompleteList_element, 1).click

      @current_page.pageAutocompleteList_element.visible?.should be_false
      
      @current_page.cancelSitelinkLink?.should be_true
      @current_page.saveSitelinkLink?.should be_true
      @current_page.saveSitelinkLink
      ajax_wait
      visit_page(SitelinksItemPage)

      @current_page.countExistingSitelinks.should == 1
      
      sleep(1)

      # puts @current_page.getNumberOfSitelinksFromCounter

      # TODO: implement tests for sitelinks
    end
  end

end

