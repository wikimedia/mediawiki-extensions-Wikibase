# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for SetLabel special page

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
label_de = generate_random_string(10)
label_de_different = generate_random_string(10)
language_code_de = "de"
item_id = ""

describe "Check SetLabel special page" do
  before :all do
    # set up: create item
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
    end
    on_page(ItemPage) do |page|
      item_id = page.get_item_id
    end
  end

  context "SetLabel functionality test" do
    it "should set label for " + language_code_de do
      visit_page(SetLabelPage) do |page|
        page.idField = ITEM_ID_PREFIX + item_id
        page.languageField = language_code_de
        page.labelField = label_de
        page.setLabelSubmit
        page.wait_for_entity_to_load
        page.navigate_to_item
        page.uls_switch_language("de", "deutsch")
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_de
      end
    end
    it "should change label for " + language_code_de do
      visit_page(SetLabelPage) do |page|
        page.idField = ITEM_ID_PREFIX + item_id
        page.languageField = language_code_de
        page.labelField = label_de_different
        page.setLabelSubmit
        page.wait_for_entity_to_load
        page.navigate_to_item
        page.uls_switch_language("de", "deutsch")
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_de_different
      end
    end
  end
end

