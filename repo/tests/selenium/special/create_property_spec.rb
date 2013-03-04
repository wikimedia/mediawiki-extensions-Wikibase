# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for NewProperty special page

require 'spec_helper'

describe "Check NewProperty special page" do
  before :all do
    # set up: switch language
    visit_page(NewPropertyPage) do |page|
      page.uls_switch_language(LANGUAGE_CODE, LANGUAGE_NAME)
    end
  end

  context "create property functionality" do
    it "should create a new property with label, description & datatype" do
      label = generate_random_string(10)
      description = generate_random_string(20)
      visit_page(NewPropertyPage) do |page|
        page.createEntityLabelField = label
        page.createEntityDescriptionField = description
        page.createEntitySubmit
        page.wait_for_entity_to_load
      end
      on_page(PropertyPage) do |page|
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description
      end
    end
    it "should create a new property with label and empty description" do
      label = generate_random_string(10)
      visit_page(NewPropertyPage) do |page|
        page.createEntityLabelField = label
        page.createEntitySubmit
        page.wait_for_entity_to_load
      end
      on_page(PropertyPage) do |page|
        page.entityLabelSpan.should == label
        page.descriptionInputField?.should be_true
      end
    end
    it "should create a new property with description and empty label" do
      description = generate_random_string(20)
      visit_page(NewPropertyPage) do |page|
        page.createEntityDescriptionField = description
        page.createEntitySubmit
        page.wait_for_entity_to_load
      end
      on_page(PropertyPage) do |page|
        page.entityDescriptionSpan.should == description
        page.labelInputField?.should be_true
      end
    end
  end

  context "create property error behavior" do
    it "should fail to create item with empty label & description" do
      visit_page(NewPropertyPage) do |page|
        page.createEntitySubmit
        page.createEntityLabelField?.should be_true
        page.createEntityDescriptionField?.should be_true
      end
    end
    it "should fail to create a new property with same label as another property" do
      description_a = generate_random_string(20)
      description_b = generate_random_string(20)
      label = generate_random_string(10)
      visit_page(NewPropertyPage) do |page|
        page.createEntityLabelField = label
        page.createEntityDescriptionField = description_a
        page.createEntitySubmit
        page.wait_for_entity_to_load
      end
      visit_page(NewPropertyPage) do |page|
        page.createEntityLabelField = label
        page.createEntityDescriptionField = description_b
        page.createEntitySubmit
        page.createEntityLabelField?.should be_true
        page.createEntityDescriptionField?.should be_true
      end
    end
    it "should check that a blocked user cannot create a new property" do
      on_page(NewPropertyPage) do |page|
        page.uls_switch_language("en", "english")
      end
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      visit_page(BlockUserPage) do |page|
        page.block_user(WIKI_BLOCKED_USERNAME, "1 hour")
      end
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_BLOCKED_USERNAME, WIKI_BLOCKED_PASSWORD)
      end
      visit_page(NewPropertyPage) do |page|
        page.mwFirstHeading.should == "User is blocked"
      end
    end
  end

  after :all do
    # teardown: unblock user, logout
    visit_page(RepoLoginPage) do |page|
      page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
    end
    visit_page(UnblockUserPage) do |page|
      page.unblock_user(WIKI_BLOCKED_USERNAME)
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end
end
