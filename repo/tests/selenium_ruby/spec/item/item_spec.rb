require 'spec_helper'

describe "Check for labels" do
  context "Check for firstHeading" do
    it "should check for firstHeading" do
      #item_id = visit_page(AddItemPage).create_new_item("mytestitem")
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

      # TODO: put this into a helper function to be reused
      while (script = @browser.execute_script("return jQuery.active")) == 1 do
        sleep(1.0/3)
      end

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

      visit_page(ItemPage)
      @current_page.itemLabelSpan.should == current_label
    end
  end

end
