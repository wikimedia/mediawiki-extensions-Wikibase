# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for checking edit conflicts

require 'spec_helper'

label = generate_random_string(10)
label_user1 = label + " changed by user 1!"
description = generate_random_string(20)
description_user1 = description + " changed by user 1!"
description_user2 = description + " changed by user 2!"
alias_a = generate_random_string(5)
sitelinks = ["it", "Pizza"]
edit_conflict_msg = "Edit not allowed: Edit conflict."
old_revid = 0

describe "Check edit-conflicts" do
  before :all do
    # set up: logout, create item as anonymous user
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
    end
  end

  context "check behaviour on edit conflicts" do
    it "should login as user 1, change description and save revid" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ORDINARY_USERNAME, WIKI_ORDINARY_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        old_revid = @browser.execute_script("return wb.getRevisionStore().getDescriptionRevision();")
        old_revid.should > 0
        page.entityDescriptionSpan.should == description
        page.editDescriptionLink
        page.descriptionInputField = description_user1
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.entityDescriptionSpan.should == description_user1
      end
    end

    it "should login as user 2 (anon), change description" do
      visit_page(RepoLoginPage) do |page|
        page.logout_user
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_user1
        page.editDescriptionLink
        page.descriptionInputField = description_user2
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.entityDescriptionSpan.should == description_user2
        @browser.execute_script("return wb.getRevisionStore().getDescriptionRevision();").should > old_revid
      end
    end

    it "should login as user 1 again, inject old revid & complain about edit conflict when changing description" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ORDINARY_USERNAME, WIKI_ORDINARY_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        js_snippet = "wb.getRevisionStore().setDescriptionRevision(parseInt(" + old_revid.to_s() + "));"
        @browser.execute_script(js_snippet)
        @browser.execute_script("return wb.getRevisionStore().getDescriptionRevision();").should == old_revid
        page.entityDescriptionSpan.should == description_user2
        page.editDescriptionLink
        page.descriptionInputField = description_user1
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_true
        page.wbErrorDetailsLink?.should be_true
        page.wbErrorDetailsLink
        page.wbErrorDetailsDiv?.should be_true
        page.wbErrorDetailsDiv_element.text.should == edit_conflict_msg
        page.cancelDescriptionLink
      end
    end

    it "should be possible to add sitelink" do
      on_page(ItemPage) do |page|
        page.add_sitelinks([sitelinks])
        page.wbErrorDiv?.should be_false
        page.count_existing_sitelinks.should == 1
      end
    end

    it "should be possible to change label" do
      on_page(ItemPage) do |page|
        page.entityLabelSpan.should == label
        page.editLabelLink
        page.labelInputField = label_user1
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_false
        page.entityLabelSpan.should == label_user1
      end
    end

    it "should be possible to add aliases" do
      on_page(ItemPage) do |page|
        page.add_aliases([alias_a])
        page.wbErrorDiv?.should be_false
        page.count_existing_aliases.should == 1
      end
    end
  end

  context "check normal behaviour" do
    it "should be possible to edit description again after a reload" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.entityDescriptionSpan.should == description_user2
        page.editDescriptionLink
        page.descriptionInputField = description_user1
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_false
        page.entityDescriptionSpan.should == description_user1
      end
    end
  end

  after :all do
    # tear down: remove all sitelinks if there are some; logout
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.remove_all_sitelinks
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end

end
