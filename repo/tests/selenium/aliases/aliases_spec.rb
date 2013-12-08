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
        page.aliases_div?.should be_true
        page.aliases_title?.should be_true
        page.aliases_list?.should be_false
        page.edit_aliases?.should be_false
        page.add_aliases?.should be_true
      end
    end
  end

  context "Check functionality of adding aliases from empty aliases" do
    it "should check that adding some aliases work properly" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load
        page.add_aliases
        page.cancel_aliases?.should be_true
        page.save_aliases?.should be_false
        page.save_aliases_disabled?.should be_true
        page.save_aliases_disabled # Clicking should not trigger any action
        page.cancel_aliases?.should be_true
        page.add_aliases_element.visible?.should be_false
        page.cancel_aliases
        page.add_aliases?.should be_true

        # adding some aliases
        page.add_aliases
        i = 0;
        while i < NUM_INITIAL_ALIASES do
          page.aliases_input_empty= aliases[i]
          i += 1;
        end
        page.save_aliases?.should be_true

        # cancel the action and check that there are still no aliases
        page.cancel_aliases?.should be_true
        page.cancel_aliases
        page.add_aliases?.should be_true

        # checking behavior of ESC key
        page.add_aliases
        page.aliases_input_empty= generate_random_string(8)
        page.aliases_input_empty_element.send_keys :escape
        page.add_aliases?.should be_true

        # again adding the aliases
        page.add_aliases
        i = 0;
        while i < NUM_INITIAL_ALIASES do
          page.aliases_input_empty= aliases[i]
          i += 1;
        end
        page.save_aliases?.should be_true

        page.save_aliases
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
        page.edit_aliases
        page.aliases_input_empty= generate_random_string(8)
        page.aliases_input_modified_element.send_keys :return
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == (num_current_aliases + 1)
        page.edit_aliases
        page.aliases_input_first_remove
        page.save_aliases
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
        page.edit_aliases
        page.edit_aliases?.should be_false
        page.cancel_aliases?.should be_true
        page.aliases_title?.should be_true
        page.aliases_list?.should be_true
        page.aliases_input_empty?.should be_true

        # check functionality of cancel
        page.cancel_aliases
        page.count_existing_aliases.should == NUM_INITIAL_ALIASES
        page.aliases_div?.should be_true
        page.aliases_title?.should be_true
        page.aliases_list?.should be_true
        page.edit_aliases?.should be_true

        # check functionality of input fields in edit mode
        page.edit_aliases
        page.aliases_input_empty?.should be_true
        page.aliases_input_modified?.should be_false
        page.aliases_input_empty= "new alias"
        page.aliases_input_empty?.should be_true
        page.aliases_input_modified?.should be_true
        page.aliases_input_remove?.should be_true
        page.save_aliases?.should be_true
        page.aliases_input_modified_element.clear
        page.aliases_input_remove
        page.aliases_input_modified?.should be_false
        page.aliases_input_empty= "new alias"
        page.aliases_input_remove
        page.aliases_input_empty?.should be_true
        page.aliases_input_modified?.should be_false
        page.cancel_aliases
      end
    end
  end

  context "Check functionality of adding more aliases" do
    it "should check that adding further aliases works properly" do
      on_page(ItemPage) do |page|
        page.wait_for_entity_to_load

        # check functionality of adding aliases
        test_alias = generate_random_string(8)
        page.edit_aliases
        page.aliases_input_empty= test_alias
        page.save_aliases
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
        page.edit_aliases
        page.aliases_input_equal?.should be_false
        page.aliases_input_empty= test_alias
        page.aliases_input_equal?.should be_true
        page.save_aliases?.should be_false
        page.aliases_input_empty= generate_random_string(8)
        page.save_aliases?.should be_true
        page.save_aliases
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
        page.edit_aliases
        page.aliases_input_first?.should be_true
        #editing an alias by deleting some chars from it
        page.aliases_input_first_element.send_keys :backspace
        page.aliases_input_first_element.send_keys :delete
        page.aliases_input_first_element.send_keys :backspace
        page.save_aliases
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
        page.edit_aliases
        page.aliases_input_empty = too_long_string
        page.save_aliases
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
        page.edit_aliases
        page.aliases_input_first_remove?.should be_true
        num_aliases = page.count_existing_aliases

        i = 0;
        while i < (num_aliases-1) do
          page.aliases_input_first_remove?.should be_true
          page.aliases_input_first_remove
          i += 1;
        end
        page.save_aliases
        ajax_wait
        page.wait_for_api_callback
        @browser.refresh
        page.wait_for_entity_to_load
        page.add_aliases?.should be_true
      end
    end
  end

  after :all do
    # tear down
  end
end

