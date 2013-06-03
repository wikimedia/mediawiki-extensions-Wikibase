# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for special pages to set label, description, aliases.

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
aliases = [generate_random_string(8), generate_random_string(8)]
label_de = generate_random_string(10)
label_de_different = generate_random_string(10)
description_de = generate_random_string(20)
description_de_different = generate_random_string(20)
alias_de = generate_random_string(8)
language_code_de = "de"
item_id = ""

describe "Check special pages to set an entity label" do
  before :all do
    # set up: create item
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
    end
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.uls_switch_language("en", "English")
      page.wait_for_entity_to_load
      page.add_aliases(aliases)
      item_id = page.get_item_id
    end
  end

  context "SetLabel functionality test" do
    it "should set label for " + language_code_de do
      visit_page(SetLabelPage) do |page|
        page.idField = ITEM_ID_PREFIX + item_id
        page.languageField = language_code_de
        page.valueField = label_de
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
        page.valueField = label_de_different
        page.setLabelSubmit
        page.wait_for_entity_to_load
        page.navigate_to_item
        page.uls_switch_language("de", "deutsch")
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label_de_different
      end
    end
  end

  context "SetDescription functionality test" do
    it "should set label for " + language_code_de do
      visit_page(SetDescriptionPage) do |page|
        page.idField = ITEM_ID_PREFIX + item_id
        page.languageField = language_code_de
        page.valueField = description_de
        page.setDescriptionSubmit
        page.wait_for_entity_to_load
        page.navigate_to_item
        page.uls_switch_language("de", "deutsch")
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_de
      end
    end
    it "should change description for " + language_code_de do
      visit_page(SetDescriptionPage) do |page|
        page.idField = ITEM_ID_PREFIX + item_id
        page.languageField = language_code_de
        page.valueField = description_de_different
        page.setDescriptionSubmit
        page.wait_for_entity_to_load
        page.navigate_to_item
        page.uls_switch_language("de", "deutsch")
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_de_different
      end
    end
  end

  context "SetAliases functionality test" do
    it "should set label for " + language_code_de do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.uls_switch_language("en", "English")
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == 2
        page.uls_switch_language("de", "Deutsch")
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == 0
      end
      visit_page(SetAliasesPage) do |page|
        page.idField = ITEM_ID_PREFIX + item_id
        page.languageField = language_code_de
        page.valueField = alias_de
        page.setAliasesSubmit
        page.wait_for_entity_to_load
        page.navigate_to_item
        page.uls_switch_language("de", "deutsch")
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == 1
        page.get_nth_alias(1).text.should == alias_de
        page.uls_switch_language("en", "English")
        page.wait_for_entity_to_load
        page.count_existing_aliases.should == 2
      end
    end
  end

  context "SetSitelink functionality test" do
    it "should set sitelink" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.count_existing_sitelinks.should == 0
      end
      visit_page(SetSitelinkPage) do |page|
        page.idField = ITEM_ID_PREFIX + item_id
        page.sitelinkSiteField = 'enwiki'
        page.sitelinkPageField = 'Bill Zuber'
        page.setSitelinkSubmit
        page.wait_for_entity_to_load
        page.count_existing_sitelinks.should == 1
        page.englishSitelink?.should be_true
        page.englishSitelink
        page.articleTitle.should == "Bill Zuber"
      end
    end
  end

  after :all do
    # tear down: remove all sitelinks
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.remove_all_sitelinks
    end
  end
end

