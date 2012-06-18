require 'spec_helper'

describe "Check functionality of add/edit/remove aliases" do
  NUM_INITIAL_ALIASES = 3
  test_alias = generate_random_string(8)
  context "Basic checks of aliases elements" do
    it "should check that there are no aliases" do
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

  context "Check functionality of adding aliases from empty aliases" do
    it "should check that adding some aliases work properly" do
      on_page(AliasesItemPage)
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.addAliases
      @current_page.cancelAliases?.should be_true
      @current_page.saveAliases?.should be_false
      @current_page.cancelAliases
      @current_page.addAliases?.should be_true

      # adding some aliases
      @current_page.addAliases
      i = 0;
      while i < NUM_INITIAL_ALIASES do
        @current_page.aliasesInputEmpty= generate_random_string(8)
        i += 1;
      end
      @current_page.saveAliases?.should be_true

      # cancel the action and check that there are still no aliases
      @current_page.cancelAliases?.should be_true
      @current_page.cancelAliases
      @current_page.addAliases?.should be_true

      # again adding the aliases
      @current_page.addAliases
      i = 0;
      while i < NUM_INITIAL_ALIASES do
        @current_page.aliasesInputEmpty= generate_random_string(8)
        i += 1;
      end
      @current_page.saveAliases?.should be_true

      @current_page.saveAliases
      ajax_wait
      @current_page.wait_for_api_callback
      @browser.refresh
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.countExistingAliases.should == NUM_INITIAL_ALIASES
    end
  end

  context "Check functionality of saving an alias by pressing return" do
    it "should check that adding an alias by pressing return works properly" do
      on_page(AliasesItemPage)
      num_current_aliases = @current_page.countExistingAliases
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.editAliases
      @current_page.aliasesInputEmpty= generate_random_string(8)
      @current_page.aliasesInputModified_element.send_keys :return
      ajax_wait
      @current_page.wait_for_api_callback
      @browser.refresh
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.countExistingAliases.should == (num_current_aliases + 1)
      @current_page.editAliases
      @current_page.aliasesInputFirstRemove
      @current_page.saveAliases
      ajax_wait
      @current_page.wait_for_api_callback
      @browser.refresh
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.countExistingAliases.should == num_current_aliases
    end
  end

  context "Check functionality and behaviour of aliases edit mode" do
    it "should check that the edit mode of aliases behaves properly" do
      on_page(AliasesItemPage)
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load

      # check edit aliases mode
      @current_page.editAliases
      @current_page.editAliases?.should be_false
      @current_page.cancelAliases?.should be_true
      @current_page.aliasesTitle?.should be_true
      @current_page.aliasesList?.should be_true
      @current_page.aliasesInputEmpty?.should be_true

      # check functionality of cancel
      @current_page.cancelAliases
      @current_page.countExistingAliases.should == NUM_INITIAL_ALIASES
      @current_page.aliasesDiv?.should be_true
      @current_page.aliasesTitle?.should be_true
      @current_page.aliasesList?.should be_true
      @current_page.editAliases?.should be_true

      # check functionality of input fields in edit mode
      @current_page.editAliases
      @current_page.aliasesInputEmpty?.should be_true
      @current_page.aliasesInputModified?.should be_false
      @current_page.aliasesInputEmpty= "new alias"
      @current_page.aliasesInputEmpty?.should be_true
      @current_page.aliasesInputModified?.should be_true
      @current_page.aliasesInputRemove?.should be_true
      @current_page.saveAliases?.should be_true
      @current_page.aliasesInputModified_element.clear
      @current_page.aliasesInputModified_element.click
      @current_page.aliasesInputEmpty?.should be_true
      @current_page.aliasesInputModified?.should be_false
      @current_page.aliasesInputEmpty= "new alias"
      @current_page.aliasesInputRemove
      @current_page.aliasesInputEmpty?.should be_true
      @current_page.aliasesInputModified?.should be_false
      @current_page.cancelAliases
    end
  end

  context "Check functionality of adding more aliases" do
    it "should check that adding further aliases works properly" do
      on_page(AliasesItemPage)
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load

      # check functionality of adding aliases
      test_alias = generate_random_string(8)
      @current_page.editAliases
      @current_page.aliasesInputEmpty= test_alias
      @current_page.saveAliases
      ajax_wait
      @current_page.wait_for_api_callback
      @browser.refresh
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.countExistingAliases.should == (NUM_INITIAL_ALIASES + 1)
    end
  end

  context "Check functionality of duplicate-alias-detection" do
    it "should check that duplicate aliases get detected and not beeing stored" do
      on_page(AliasesItemPage)
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load

      # checking detection of duplicate aliases
      @current_page.editAliases
      @current_page.aliasesInputEqual?.should be_false
      @current_page.aliasesInputEmpty= test_alias
      @current_page.aliasesInputEqual?.should be_true
      @current_page.saveAliases?.should be_false
      @current_page.aliasesInputEmpty= generate_random_string(8)
      @current_page.saveAliases?.should be_true
      @current_page.saveAliases
      ajax_wait
      @current_page.wait_for_api_callback
      @browser.refresh
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.countExistingAliases.should == (NUM_INITIAL_ALIASES + 2)
    end
  end

  context "Check functionality of editing existing aliases" do
    it "should check that edit existing aliases work properly" do
      on_page(AliasesItemPage)
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load

      # checking functionality of editing aliases
      @current_page.editAliases
      @current_page.aliasesInputFirst?.should be_true
      #editing an alias by deleting some chars from it
      @current_page.aliasesInputFirst_element.send_keys :backspace
      @current_page.aliasesInputFirst_element.send_keys :delete
      @current_page.aliasesInputFirst_element.send_keys :backspace
      @current_page.saveAliases
      ajax_wait
      @current_page.wait_for_api_callback
      @browser.refresh
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.countExistingAliases.should == (NUM_INITIAL_ALIASES + 2)
    end
  end

  context "Check functionality of removing aliases" do
    it "should check that removing aliases work properly" do
      on_page(AliasesItemPage)
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load

      # checking functionality of removing aliases
      @current_page.editAliases
      @current_page.aliasesInputFirstRemove?.should be_true
      num_aliases = @current_page.countExistingAliases

      i = 0;
      while i < (num_aliases-1) do
        @current_page.aliasesInputFirstRemove?.should be_true
        @current_page.aliasesInputFirstRemove
        i += 1;
      end
      @current_page.saveAliases
      ajax_wait
      @current_page.wait_for_api_callback
      @browser.refresh
      @current_page.wait_for_aliases_to_load
      @current_page.wait_for_item_to_load
      @current_page.addAliases?.should be_true
    end
  end
end

