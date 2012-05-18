require 'spec_helper'

describe "Check functionality of edit label" do
  context "Check for edit label" do
    it "should check for edit label" do
      visit_page(ItemPage)
      @current_page.firstHeading.should be_true
      @current_page.itemLabelSpan.should be_true
      current_label = @current_page.itemLabelSpan
      changed_label = current_label + "_fooo"
      @browser.title.include? current_label
      @current_page.itemLabelSpan.should == current_label
      @current_page.editLabelLink?.should be_true
      @current_page.cancelLabelLink?.should be_false
      @current_page.editLabelLink
      @current_page.editLabelLink?.should be_false
      @current_page.cancelLabelLink?.should be_true
      @current_page.saveLabelLinkDisabled?.should be_true
      @current_page.labelInputField.should be_true
      @current_page.labelInputField_element.clear
      @current_page.labelInputField = changed_label
      @current_page.saveLabelLink?.should be_true
      @current_page.cancelLabelLink
      @current_page.editLabelLink?.should be_true
      @current_page.cancelLabelLink?.should be_false
      @current_page.itemLabelSpan.should == current_label
      @current_page.editLabelLink
      @current_page.labelInputField_element.clear
      @current_page.labelInputField = changed_label
      @current_page.saveLabelLink
      @current_page.itemLabelSpan.should == changed_label
      ajax_wait
      @browser.refresh
      @current_page.itemLabelSpan.should == changed_label
      @browser.title.include? changed_label
      @current_page.editLabelLink
      @current_page.labelInputField_element.clear
      @current_page.labelInputField = current_label
      @current_page.saveLabelLink
      @current_page.itemLabelSpan.should == current_label
      ajax_wait
      @browser.refresh
      @current_page.itemLabelSpan.should == current_label
      @browser.title.include? current_label
    end
  end
end
