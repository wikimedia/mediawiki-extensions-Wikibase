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
  before :all do
    # set up: create item, enter label, description and aliases
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
      page.wait_for_entity_to_load
      page.add_aliases([alias_a, alias_b, alias_c])
    end
  end
  context "check for label and ID on recentChanges" do
    it "should check if label and ID are displayed and link leads to the correct item" do
      visit_page(RepoRecentChangesPage) do |page|
        page.firstResultLabelSpan?.should be_true
        page.firstResultIdSpan?.should be_true
        page.firstResultLabelSpan.should == label
        page.firstResultIdSpan.should == "(" + ITEM_ID_PREFIX + page.get_item_id + ")"
        page.firstResultLink?.should be_true
        page.firstResultLink
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
      end
    end
  end
  after :all do
    # tear down
  end
end
