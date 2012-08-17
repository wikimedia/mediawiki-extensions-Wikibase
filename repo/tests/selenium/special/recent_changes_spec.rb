# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for recentChanges special page

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
alias_a = generate_random_string(5)
alias_b = generate_random_string(5)
alias_c = generate_random_string(5)

describe "Check functionality of recentChanges special page" do
  context "recentChanges test setup" do
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
      end
    end
  end

  context "check for label and ID on recentChanges" do
    it "should check if label and ID are displayed and link leads to the correct item" do
      visit_page(RecentChangesPage) do |page|
        page.firstResultLabelSpan?.should be_true
        page.firstResultIdSpan?.should be_true
        page.firstResultLabelSpan.should == label
        page.firstResultIdSpan.should == "(q" + page.get_item_id + ")"
        page.firstResultLink?.should be_true
        page.firstResultLink
        page.wait_for_item_to_load
        page.itemLabelSpan.should == label
      end
    end
  end
end
