require 'spec_helper'

describe "Check for bugs" do
  context "startup" do
    it "just some simple startup checks" do
      # create new item
      visit_page(AliasesItemPage)
      @current_page.create_new_item(generate_random_string(10), generate_random_string(20))
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load

      # check for necessary elements
      @current_page.aliasesDiv?.should be_true
      @current_page.aliasesTitle?.should be_true
      @current_page.aliasesList?.should be_false
      @current_page.editAliases?.should be_false
      @current_page.addAliases?.should be_true
    end
  end

  context "bug: add-button appearing when it sould not" do
    it "bug: add-button appearing when it sould not" do
      on_page(AliasesItemPage)
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.addAliases
      @current_page.addAliasesDiv_element.present?.should be_false
      @current_page.cancelAliases?.should be_true
      @current_page.cancelAliases
      @current_page.addAliases?.should be_true
      @current_page.cancelAliases?.should be_false
      @current_page.addAliases
      @current_page.addAliasesDiv_element.present?.should be_false
      @current_page.cancelAliases?.should be_true
      @current_page.cancelAliases
    end
  end
end

