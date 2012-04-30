
require 'spec_helper'

describe "Check for labels" do
  context "Check for firstHeading" do
    it "should check for firstHeading" do 
      item_id = visit_page(AddItemPage).create_new_item("mytestitem")
      visit_page(ItemPage)
      @current_page.firstHeading.should be_true
      @current_page.itemLabelSpan.should be_true
      @current_page.itemLabelSpan.should == "Austria"
    end
  end
  
end
