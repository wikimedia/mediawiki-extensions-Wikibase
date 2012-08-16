# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for undo/history/oldrevision

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
alias_a = generate_random_string(5)
alias_b = generate_random_string(5)
alias_c = generate_random_string(5)
sitelinks = [["en", "London"], ["de", "London"]]
sitelink_changed = "London Olympics"
changed = "_changed"

describe "Check undo/history/oldrevision" do

  context "undo test setup" do
    it "should create item, enter label, description and aliases" do
      visit_page(AliasesItemPage) do |page|
        page.create_new_item(label, description)
        page.wait_for_aliases_to_load
        page.wait_for_item_to_load
        page.addAliases
        page.aliasesInputEmpty= alias_a
        page.aliasesInputEmpty= alias_b
        page.aliasesInputEmpty= alias_c
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        page.add_sitelink(sitelinks[0][0], sitelinks[0][1])
      end
    end
    it "should check revision count on history page" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.count_revisions.should == 4
      end
    end
    it "should make some changes to item" do
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.editLabelLink
        page.labelInputField= label + changed
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.editDescriptionLink
        page.descriptionInputField= description + changed
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.editAliases
        page.aliasesInputFirst= alias_a + changed
        page.saveAliases
        ajax_wait
        page.wait_for_api_callback
        page.editSitelinkLink
        page.pageInputField= sitelink_changed
        ajax_wait
        page.saveSitelinkLink
        ajax_wait
        page.wait_for_api_callback
      end
    end
    it "should check revision count on history page" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.count_revisions.should == 8
      end
    end
  end

  context "view old revision test" do
    it "should check functionality of viewing an old revision" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.oldRevision5?.should be_true
        page.oldRevision5
        sleep 1
      end
      on_page(AliasesItemPage) do |page|
        page.itemLabelSpan.should == label
        page.itemDescriptionSpan.should == description
        page.getNthAlias(3).text.should == alias_c
        page.editLabelLink?.should be_false
        page.editDescriptionLink?.should be_false
        page.addAliases?.should be_false
        page.editAliases?.should be_false
        page.addSitelinkLink?.should be_false
        page.editSitelinkLink?.should be_false
      end
    end
  end

  context "undo functionality test" do
    it "should undo label change" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo4
        page.undoDel.should == label + changed
        page.undoIns.should == label
        page.undoDelTitle.should == "label / en"
        page.undoInsTitle.should == "label / en"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.itemLabelSpan.should == label
      end
    end
    it "should try to do the same undo again" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo5
        page.undoDel?.should be_false
        page.undoIns?.should be_false
      end
    end
    it "should undo description change" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo4
        page.undoDel.should == description + changed
        page.undoIns.should == description
        page.undoDelTitle.should == "description / en"
        page.undoInsTitle.should == "description / en"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.itemDescriptionSpan.should == description
      end
    end
    it "should undo the undo of the description change" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo1
        page.undoDel.should == description
        page.undoIns.should == description + changed
        page.undoDelTitle.should == "description / en"
        page.undoInsTitle.should == "description / en"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.itemDescriptionSpan.should == description + changed
      end
    end
    it "should add a second siteling and undo the change" do
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.add_sitelink(sitelinks[1][0], sitelinks[1][1])
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo1
        page.undoDel.should == sitelinks[1][1]
        page.undoIns?.should be_false
        page.undoDelTitle.should == "links / dewiki"
        page.undoInsTitle.should == "links / dewiki"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.getNumberOfSitelinksFromCounter.should == 1
      end
    end
    it "should undo the change on the first language link" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo6
        page.undoDel.should == sitelink_changed
        page.undoIns.should == sitelinks[0][1]
        page.undoDelTitle.should == "links / enwiki"
        page.undoInsTitle.should == "links / enwiki"
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.getNumberOfSitelinksFromCounter.should == 1
        page.englishSitelink_element.text.should == sitelinks[0][1]
      end
    end
    it "should undo the aliases change" do
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.undo8
        page.undoDel.should == alias_a + changed
        page.undoIns.should == alias_a
        page.undoSave
      end
    end
    it "should check the effect of the undo" do
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.getNthAlias(3).text.should == alias_a
      end
    end
  end

  context "undo test teardown" do
    it "should remove all sitelinks" do
      on_page(AliasesItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.wait_for_sitelinks_to_load
        page.remove_all_sitelinks
      end
    end
  end
end
