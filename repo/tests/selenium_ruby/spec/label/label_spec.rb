
require 'spec_helper'

describe "Check for labels" do
  context "Check for firstHeading" do
    it "should check for firstHeading" do 
      visit_page(LabelPage)
      @current_page.firstHeading.should be_true
    end
  end
  
end
