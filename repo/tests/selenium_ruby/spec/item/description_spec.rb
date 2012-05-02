require 'spec_helper'

describe "Check functionality of edit description" do

  context "Check for item description UI" do
    it "should check for edit descriptio" do
      visit_page(ItemPage)
      @current_page.itemDescriptionSpan.should be_true
      current_description = @current_page.itemDescriptionSpan
      changed_description = current_description + " Adding something."
      @current_page.itemDescriptionSpan.should == current_description
      @current_page.editDescriptionLink?.should be_true
      @current_page.cancelDescriptionLink?.should be_false
      @current_page.editDescriptionLink
      @current_page.editDescriptionLink?.should be_false
      @current_page.cancelDescriptionLink?.should be_true
      @current_page.saveDescriptionLinkDisabled?.should be_true
      @current_page.descriptionInputField.should be_true
      @current_page.descriptionInputField.clear
      @current_page.descriptionInputField = changed_description
      @current_page.saveDescriptionLink?.should be_true
      @current_page.cancelDescriptionLink
      @current_page.editDescriptionLink?.should be_true
      @current_page.cancelDescriptionLink?.should be_false
      @current_page.itemDescriptionSpan.should == current_description
      @current_page.editDescriptionLink
      @current_page.descriptionInputField.clear
      @current_page.descriptionInputField = changed_description
      @current_page.saveDescriptionLink
      @current_page.itemDescriptionSpan.should == changed_description
      ajax_wait
      # TODO: is there a better method for reloading?
      visit_page(ItemPage)
      @current_page.itemDescriptionSpan.should == changed_description
      @current_page.editDescriptionLink
      @current_page.descriptionInputField.clear
      @current_page.descriptionInputField = current_description
      @current_page.saveDescriptionLink
      @current_page.itemDescriptionSpan.should == current_description
      ajax_wait
      # TODO: is there a better method for reloading?
      visit_page(ItemPage)
      @current_page.itemDescriptionSpan.should == current_description
    end
  end

end

