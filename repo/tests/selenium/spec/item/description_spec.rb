require 'spec_helper'

describe "Check functionality of edit description" do

  context "Check for item description UI" do
    it "should check for edit description" do
      visit_page(ItemPage)
      @current_page.wait_for_item_to_load
      @current_page.itemDescriptionSpan.should be_true
      current_description = @current_page.itemDescriptionSpan
      changed_description = current_description + " Adding something."
      @current_page.itemDescriptionSpan.should == current_description
      @current_page.wait_for_item_to_load
      @current_page.editDescriptionLink?.should be_true
      @current_page.cancelDescriptionLink?.should be_false
      @current_page.editDescriptionLink
      @current_page.editDescriptionLink?.should be_false
      @current_page.cancelDescriptionLink?.should be_true
      @current_page.saveDescriptionLinkDisabled?.should be_true
      @current_page.descriptionInputField.should be_true
      @current_page.descriptionInputField_element.clear
      @current_page.descriptionInputField = changed_description
      @current_page.saveDescriptionLink?.should be_true
      @current_page.cancelDescriptionLink
      @current_page.editDescriptionLink?.should be_true
      @current_page.cancelDescriptionLink?.should be_false
      @current_page.itemDescriptionSpan.should == current_description
      @current_page.editDescriptionLink
      @current_page.descriptionInputField_element.clear
      @current_page.descriptionInputField = changed_description
      @current_page.saveDescriptionLink
      @current_page.apiCallWaitingMessage?.should be_true
      ajax_wait
      @current_page.wait_for_api_callback
      @current_page.itemDescriptionSpan.should == changed_description

      @browser.refresh
      @current_page.wait_for_item_to_load
      @current_page.itemDescriptionSpan.should == changed_description
      @current_page.editDescriptionLink
      @current_page.descriptionInputField_element.clear
      @current_page.descriptionInputField = current_description
      @current_page.saveDescriptionLink
      @current_page.apiCallWaitingMessage?.should be_true
      ajax_wait
      @current_page.wait_for_api_callback
      @current_page.itemDescriptionSpan.should == current_description
      
      @browser.refresh
      @current_page.wait_for_item_to_load
      @current_page.itemDescriptionSpan.should == current_description
    end
  end

end

