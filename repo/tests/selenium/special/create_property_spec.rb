# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for CreateProperty special page

require 'spec_helper'

describe "Check CreateProperty special page" do
  before :all do
    # set up: switch language
    visit_page(CreatePropertyPage) do |page|
      page.uls_switch_language(LANGUAGE)
    end
  end
  context "create property functionality" do
    it "should fail to create item with empty label & description" do
      visit_page(CreatePropertyPage) do |page|
        page.createEntitySubmit
        page.createEntityLabelField?.should be_true
        page.createEntityDescriptionField?.should be_true
      end
    end
    it "should create a new property with label, description & datatype" do
      label = generate_random_string(10)
      description = generate_random_string(20)
      visit_page(CreatePropertyPage) do |page|
        page.createEntityLabelField = label
        page.createEntityDescriptionField = description
        page.createEntitySubmit
        page.wait_for_entity_to_load
      end
      on_page(PropertyPage) do |page|
        page.itemLabelSpan.should == label
        page.itemDescriptionSpan.should == description
      end
    end
  end

  after :all do
    # teardown
  end
end
