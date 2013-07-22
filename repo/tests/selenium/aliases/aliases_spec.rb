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
  aliases = [generate_random_string(8), generate_random_string(8), '0']
  test_alias = generate_random_string(8)

  before :all do
    # setup
    visit_page(CreateItemPage) do |page|
      page.create_new_item(generate_random_string(10), generate_random_string(20))
    end
  end

  context "Basic checks of aliases elements" do
    it "should check that there are no aliases" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
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
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.addAliases
        page.cancelAliases?.should be_true
        page.saveAliases?.should be_false
        page.saveAliasesDisabled?.should be_true
        page.saveAliasesDisabled # Clicking should not trigger any action
        page.cancelAliases?.should be_true
        page.addAliases_element.visible?.should be_false
        page.cancelAliases
        page.addAliases?.should be_true

        # adding some aliases
        page.addAliases
        i = 0;
        while i < NUM_INITIAL_ALIASES do
          page.aliasesInputEmpty= aliases[i]
          i += 1;
        end
        page.saveAliases?.should be_true

        # cancel the action and check that there are still no aliases
        page.cancelAliases?.should be_true
        page.cancelAliases
        page.addAliases?.should be_true

        # checking behavior of ESC key
        page.addAliases
        page.aliasesInputEmpty= generate_random_string(8)
        page.aliasesInputEmpty_element.send_keys :escape
        page.addAliases?.should be_true

        # again adding the aliases
        page.addAliases
        i = 0;
        while i < NUM_INITIAL_ALIASES do
          page.aliasesInputEmpty= aliases[i]
          i += 1;
        end
        page.saveAliases?.should be_true

        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == NUM_INITIAL_ALIASES
      end
    end
  end

  context "Check functionality of saving an alias by pressing return" do
    it "should check that adding an alias by pressing return works properly" do
      on_page(ItemPage) do |page|
        num_current_aliases = page.count_existing_aliases
        page.wait_for_entity_to_load
        page.editAliases
        page.aliasesInputEmpty= generate_random_string(8)
        page.aliasesInputModified_element.send_keys :return
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == (num_current_aliases + 1)
        page.editAliases
        page.aliasesInputFirstRemove
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == num_current_aliases
      end
    end
  end

  context "Check functionality and behavior of aliases edit mode" do
    it "should check that the edit mode of aliases behaves properly" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load

        # check edit aliases mode
        page.editAliases
        page.editAliases?.should be_false
        page.cancelAliases?.should be_true
        page.aliasesTitle?.should be_true
        page.aliasesList?.should be_true
        page.aliasesInputEmpty?.should be_true

        # check functionality of cancel
        page.cancelAliases
        page.count_existing_aliases.should == NUM_INITIAL_ALIASES
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
        page.aliasesInputRemove
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
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load

        # check functionality of adding aliases
        test_alias = generate_random_string(8)
        page.editAliases
        page.aliasesInputEmpty= test_alias
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == (NUM_INITIAL_ALIASES + 1)
      end
    end
  end

  context "Check functionality of duplicate-alias-detection" do
    it "should check that duplicate aliases get detected and cannot be stored" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load

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
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == (NUM_INITIAL_ALIASES + 2)
      end
    end
  end

  context "Check functionality of editing existing aliases" do
    it "should check that edit existing aliases work properly" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load

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
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == (NUM_INITIAL_ALIASES + 2)
      end
    end
  end

  context "Check for special inputs for aliases" do
    it "should check for length constraint (assuming max 250 chars)" do
      on_page(ItemPage) do |page|
        too_long_string =
        "loooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo" +
        "oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo" +
        "oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooong";
        page.wait_for_entity_to_load
        page.editAliases
        page.aliasesInputEmpty = too_long_string
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_true
      end
    end
  end

  context "Check functionality of removing aliases" do
    it "should check that removing aliases work properly" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load

        # checking functionality of removing aliases
        page.editAliases
        page.aliasesInputFirstRemove?.should be_true
        num_aliases = page.count_existing_aliases

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
        page.wait_for_entity_to_load
        page.addAliases?.should be_true
      end
    end
  end

  after :all do
    # tear down
  end
end

