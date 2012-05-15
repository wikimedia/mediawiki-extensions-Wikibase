require 'spec_helper'

describe "Check functionality of edit description" do

  context "Check for item description UI" do
    it "should check for edit description" do
      initial_label = generate_random_string(10)
      initial_description = generate_random_string(20)

      visit_page(EmptyItemPage)
      @current_page.wait_for_item_to_load
      @current_page.labelInputField.should be_true
      @current_page.editLabelLink?.should be_false
      @current_page.saveLabelLinkDisabled?.should be_true
      @current_page.cancelLabelLinkDisabled?.should be_true
      @current_page.labelInputField_element.clear
      @current_page.labelInputField = initial_label
      @current_page.saveLabelLink?.should be_true
      @current_page.saveLabelLink
      @current_page.apiCallWaitingMessage?.should be_true
      ajax_wait
      @current_page.wait_for_api_callback
      @current_page.itemLabelSpan.should == initial_label

      @browser.refresh
      @current_page.wait_for_item_to_load
      @current_page.itemLabelSpan.should == initial_label

      @browser.refresh
      @current_page.wait_for_item_to_load
      @current_page.descriptionInputField.should be_true
      @current_page.editDescriptionLink?.should be_false
      @current_page.saveDescriptionLinkDisabled?.should be_true
      @current_page.cancelDescriptionLinkDisabled?.should be_true
      @current_page.descriptionInputField_element.clear
      @current_page.descriptionInputField = initial_description
      @current_page.saveDescriptionLink?.should be_true
      @current_page.saveDescriptionLink
      @current_page.apiCallWaitingMessage?.should be_true
      ajax_wait
      @current_page.wait_for_api_callback

      @browser.refresh
      @current_page.wait_for_item_to_load
      @current_page.itemDescriptionSpan.should == initial_description

    end
  end

end

