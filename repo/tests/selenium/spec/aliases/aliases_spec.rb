# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for aliases

require 'spec_helper'

describe "Check functionality of add/edit/remove aliases" do
  NUM_INITIAL_ALIASES = 3
  test_alias = generate_random_string(8)
  context "Basic checks of aliases elements" do
    it "should check that there are no aliases" do
      # create new item
      visit_page(AliasesItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load

        # check for necessary elements
        page.aliasesDiv?.should be_true
        page.aliasesTitle?.should be_true
        page.aliasesList?.should be_false
        page.editAliases?.should be_false
        page.addAliases?.should be_true
      end
    end
  end

  context "Check functionality of adding aliases from empty aliases" do
    it "should check that adding some aliases work properly" do
      on_page(AliasesItemPage) do |page|
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases
        page.cancelAliases?.should be_true
        page.saveAliases?.should be_false
        page.cancelAliases
        page.addAliases?.should be_true

        # adding some aliases
        page.addAliases
        i = 0;
        while i < NUM_INITIAL_ALIASES do
          page.aliasesInputEmpty= generate_random_string(8)
          i += 1;
        end
        page.saveAliases?.should be_true

        # cancel the action and check that there are still no aliases
        page.cancelAliases?.should be_true
        page.cancelAliases
        page.addAliases?.should be_true

        # checking behaviour of ESC key
        page.addAliases
        page.aliasesInputEmpty= generate_random_string(8)
        page.aliasesInputEmpty_element.send_keys :escape
        page.addAliases?.should be_true

        # again adding the aliases
        page.addAliases
        i = 0;
        while i < NUM_INITIAL_ALIASES do
          page.aliasesInputEmpty= generate_random_string(8)
          i += 1;
        end
        page.saveAliases?.should be_true

        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.countExistingAliases.should == NUM_INITIAL_ALIASES
      end
    end
  end

  context "Check functionality of saving an alias by pressing return" do
    it "should check that adding an alias by pressing return works properly" do
      on_page(AliasesItemPage) do |page|
        num_current_aliases = page.countExistingAliases
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.editAliases
        page.aliasesInputEmpty= generate_random_string(8)
        page.aliasesInputModified_element.send_keys :return
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.countExistingAliases.should == (num_current_aliases + 1)
        page.editAliases
        page.aliasesInputFirstRemove
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.countExistingAliases.should == num_current_aliases
      end
    end
  end

  context "Check functionality and behaviour of aliases edit mode" do
    it "should check that the edit mode of aliases behaves properly" do
      on_page(AliasesItemPage) do |page|
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load

        # check edit aliases mode
        page.editAliases
        page.editAliases?.should be_false
        page.cancelAliases?.should be_true
        page.aliasesTitle?.should be_true
        page.aliasesListInEditMode?.should be_true
        page.aliasesInputEmpty?.should be_true

        # check functionality of cancel
        page.cancelAliases
        page.countExistingAliases.should == NUM_INITIAL_ALIASES
        page.aliasesDiv?.should be_true
        page.aliasesTitle?.should be_true
        page.aliasesList?.should be_true
        page.editAliases?.should be_true

        # check functionality of input fields in edit mode
        page.editAliases
        page.aliasesInputEmpty?.should be_true
        page.aliasesInputModified?.should be_false
        page.aliasesInputEmpty= "new alias"
        page.aliasesInputEmpty?.should be_true
        page.aliasesInputModified?.should be_true
        page.aliasesInputRemove?.should be_true
        page.saveAliases?.should be_true
        page.aliasesInputModified_element.clear
        page.aliasesInputModified_element.click
        page.aliasesInputEmpty?.should be_true
        page.aliasesInputModified?.should be_false
        page.aliasesInputEmpty= "new alias"
        page.aliasesInputRemove
        page.aliasesInputEmpty?.should be_true
        page.aliasesInputModified?.should be_false
        page.cancelAliases
      end
    end
  end

  context "Check functionality of adding more aliases" do
    it "should check that adding further aliases works properly" do
      on_page(AliasesItemPage) do |page|
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load

        # check functionality of adding aliases
        test_alias = generate_random_string(8)
        page.editAliases
        page.aliasesInputEmpty= test_alias
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.countExistingAliases.should == (NUM_INITIAL_ALIASES + 1)
      end
    end
  end

  context "Check functionality of duplicate-alias-detection" do
    it "should check that duplicate aliases get detected and not beeing stored" do
      on_page(AliasesItemPage) do |page|
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load

        # checking detection of duplicate aliases
        page.editAliases
        page.aliasesInputEqual?.should be_false
        page.aliasesInputEmpty= test_alias
        page.aliasesInputEqual?.should be_true
        page.saveAliases?.should be_false
        page.aliasesInputEmpty= generate_random_string(8)
        page.saveAliases?.should be_true
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.countExistingAliases.should == (NUM_INITIAL_ALIASES + 2)
      end
    end
  end

  context "Check functionality of editing existing aliases" do
    it "should check that edit existing aliases work properly" do
      on_page(AliasesItemPage) do |page|
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load

        # checking functionality of editing aliases
        page.editAliases
        page.aliasesInputFirst?.should be_true
        #editing an alias by deleting some chars from it
        page.aliasesInputFirst_element.send_keys :backspace
        page.aliasesInputFirst_element.send_keys :delete
        page.aliasesInputFirst_element.send_keys :backspace
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.countExistingAliases.should == (NUM_INITIAL_ALIASES + 2)
      end
    end
  end

  context "Check functionality of removing aliases" do
    it "should check that removing aliases work properly" do
      on_page(AliasesItemPage) do |page|
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load

        # checking functionality of removing aliases
        page.editAliases
        page.aliasesInputFirstRemove?.should be_true
        num_aliases = page.countExistingAliases

        i = 0;
        while i < (num_aliases-1) do
          page.aliasesInputFirstRemove?.should be_true
          page.aliasesInputFirstRemove
          i += 1;
        end
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases?.should be_true
      end
    end
  end
end

