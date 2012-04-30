require 'spec_helper'

describe "Check functionality of edit label/description UI" do
  context "Check for edit label UI" do
    it "should check for edit label / cancel / save" do
      visit_page(ItemPage)
      @current_page.firstHeading.should be_true
      @current_page.itemLabelSpan.should be_true
      current_label = @current_page.itemLabelSpan
      changed_label = current_label + "_fooo"
      @current_page.itemLabelSpan.should == current_label
      @current_page.editLabelLink?.should be_true
      @current_page.cancelLabelLink?.should be_false
      @current_page.editLabelLink
      @current_page.editLabelLink?.should be_false
      @current_page.cancelLabelLink?.should be_true
      @current_page.saveLabelLinkDisabled?.should be_true
      @current_page.labelInputField.should be_true
      @current_page.labelInputField.clear
      @current_page.labelInputField = changed_label
      @current_page.saveLabelLink?.should be_true
      @current_page.cancelLabelLink
      @current_page.editLabelLink?.should be_true
      @current_page.cancelLabelLink?.should be_false
      @current_page.itemLabelSpan.should == current_label
      @current_page.editLabelLink
      @current_page.labelInputField.clear
      @current_page.labelInputField = changed_label
      @current_page.saveLabelLink
      @current_page.itemLabelSpan.should == changed_label

      # TODO: check if the browser is chrome and force a sleep for ajax-calls
      # if @browser.driver == "chrome" ....

      # TODO: put this into a helper function to be reused
      while (script = @browser.execute_script("return jQuery.active")) == 1 do
        #puts @browser.pending_requests
        sleep(1.0/3)
      end
        #puts @browser.pending_requests

      # TODO: is there a better method for reloading?
      visit_page(ItemPage)
      @current_page.itemLabelSpan.should == changed_label

      @current_page.editLabelLink
      @current_page.labelInputField.clear
      @current_page.labelInputField = current_label
      @current_page.saveLabelLink
      @current_page.itemLabelSpan.should == current_label

      # TODO: put this into a helper function to be reused
      while (script = @browser.execute_script("return jQuery.active")) == 1 do
        sleep(1.0/3)
      end

      # TODO: is there a better method for reloading?
      visit_page(ItemPage)
      @current_page.itemLabelSpan.should == current_label
    end
  end
  
=begin
  context "Check for item description UI" do
    it "should check for edit description / cancel / save" do
      visit_page(ItemPage)
      @current_page.firstHeading.should be_true
      @current_page.itemLabelSpan.should be_true
      current_label = @current_page.itemLabelSpan
      changed_label = current_label + "_fooo"
      @current_page.itemLabelSpan.should == current_label
      @current_page.editLink?.should be_true
      @current_page.cancelLink?.should be_false
      @current_page.editLink
      @current_page.editLink?.should be_false
      @current_page.cancelLink?.should be_true
      @current_page.saveLinkDisabled?.should be_true
      @current_page.valueInputField.should be_true
      @current_page.valueInputField.clear
      @current_page.valueInputField = changed_label
      @current_page.saveLink?.should be_true
      @current_page.cancelLink
      @current_page.editLink?.should be_true
      @current_page.cancelLink?.should be_false
      @current_page.itemLabelSpan.should == current_label
      @current_page.editLink
      @current_page.valueInputField.clear
      @current_page.valueInputField = changed_label
      @current_page.saveLink
      @current_page.itemLabelSpan.should == changed_label

      # TODO: check if the browser is chrome and force a sleep for ajax-calls
      # if @browser.driver == "chrome" ....

      # TODO: put this into a helper function to be reused
      while (script = @browser.execute_script("return jQuery.active")) == 1 do
        sleep(1.0/3)
      end

      # TODO: is there a better method for reloading?
      visit_page(ItemPage)
      @current_page.itemLabelSpan.should == changed_label

      @current_page.editLink
      @current_page.valueInputField.clear
      @current_page.valueInputField = current_label
      @current_page.saveLink
      @current_page.itemLabelSpan.should == current_label

      # TODO: put this into a helper function to be reused
      while (script = @browser.execute_script("return jQuery.active")) == 1 do
        sleep(1.0/3)
      end

      # TODO: is there a better method for reloading?
      visit_page(ItemPage)
      @current_page.itemLabelSpan.should == current_label
    end
  end
=end

end
