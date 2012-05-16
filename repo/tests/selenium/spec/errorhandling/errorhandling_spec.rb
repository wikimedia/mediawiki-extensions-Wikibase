require 'spec_helper'

describe "Check functionality of errorhandling" do

  context "Check for errorhandling of UI" do
    it "should check that errorhandling is done correctly by showing a error-tooltip" do
      visit_page(SitelinksItemPage)
      @current_page.wait_for_sitelinks_to_load
      @current_page.addSitelinkLink
      @current_page.siteIdInputField="en"
      ajax_wait
      @current_page.wait_until do
        @current_page.siteIdAutocompleteList_element.visible?
      end
      @current_page.siteIdInputField_element.send_keys :arrow_down
      aCListElement = @current_page.getNthElementInAutocompleteList(@current_page.siteIdAutocompleteList_element, 1)
      aCListElement.visible?.should be_true
      aCListElement.click
      @current_page.pageInputField="Germa"
      ajax_wait
      @current_page.wait_until do
        @current_page.pageAutocompleteList_element.visible?
      end

      @current_page.pageInputField_element.send_keys :arrow_down
      @current_page.pageInputField_element.send_keys :return
      if @current_page.saveSitelinkLink?
        @current_page.saveSitelinkLink
      end
      ajax_wait
      @current_page.wait_for_api_callback
    end
  end

end

