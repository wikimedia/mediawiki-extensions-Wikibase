require 'spec_helper'

describe "Check for correct page title" do
=begin
  context "Check for correct page title" do
    it "should check for correct page title" do
      visit_page(ItemPage)
      @current_page.firstHeading.should be_true
      @current_page.itemLabelSpan.should be_true
      current_label = @current_page.itemLabelSpan
      changed_label = current_label + "_fooo"
      @current_page.has_expected_title?
    end
  end
=end

end

