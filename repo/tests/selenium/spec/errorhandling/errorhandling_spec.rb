require 'spec_helper'

# This test will only work when RIGHTS and TOKENS are switched on!

describe "Check functionality of errorhandling" do

  context "Check for errorhandling of UI" do
    it "should check that errorhandling is done correctly by showing a error-tooltip" do

      visit_page(ErrorProducingPage)
      @current_page.wait_for_item_to_load
      @current_page.editLabelLink?.should be_true
      @current_page.editLabelLink
      @current_page.labelInputField.should be_true
      @current_page.labelInputField = "youCannotSaveMe"
      @current_page.saveLabelLink?.should be_true
      @current_page.saveLabelLink
      @current_page.apiCallWaitingMessage?.should be_true
      ajax_wait
      @current_page.wait_for_api_callback
      @current_page.wbErrorDiv?.should be_true
      @current_page.wbErrorDetailsLink?.should be_true
      @current_page.wbErrorDetailsLink
      
    end
  end

end

