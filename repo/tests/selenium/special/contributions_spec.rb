# Wikidata UI tests
#
# Author:: Anja Jentzsch (anja.jentzsch@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for Contributions special page

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)

describe "Check functionality of Contributions special page" do
  before :all do
    # set up: create item, enter label, description
    visit_page(RepoLoginPage) do |page|
      page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
      page.wait_for_entity_to_load
    end
  end
  context "check for label and ID on Contributions" do
    it "should check if label and ID are displayed and link leads to the correct item" do
      visit(ContributionsPage) do |page|
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

