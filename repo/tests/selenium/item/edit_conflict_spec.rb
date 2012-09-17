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
    visit_page(LoginPage) do |page|
      page.logout_user
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
    end
  end

  context "check behaviour on edit conflicts" do
    it "should login as user 1, change description and save revid" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_ORDINARY_USERNAME, WIKI_ORDINARY_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.itemDescriptionSpan.should == description
        page.editDescriptionLink
        page.descriptionInputField = description_user1
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.itemDescriptionSpan.should == description_user1
        old_revid = @browser.execute_script("return mw.config.get('wgCurRevisionId');")
        old_revid.should > 0
      end
    end

    it "should login as user 2 (anon), change and description" do
      visit_page(LoginPage) do |page|
        page.logout_user
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.itemDescriptionSpan.should == description_user1
        page.editDescriptionLink
        page.descriptionInputField = description_user2
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.itemDescriptionSpan.should == description_user2
        old_revid.should_not == @browser.execute_script("return mw.config.get('wgCurRevisionId');")
      end
    end

    it "should login as user 1 again & inject old revid" do
      visit_page(LoginPage) do |page|
        page.login_with(WIKI_ORDINARY_USERNAME, WIKI_ORDINARY_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        js_snippet = "mw.config.set('wgCurRevisionId', " + old_revid.to_s() + ");"
        @browser.execute_script(js_snippet)
        old_revid.should == @browser.execute_script("return mw.config.get('wgCurRevisionId');")
      end
    end

    it "should complain about edit conflict when adding sitelink" do
      on_page(ItemPage) do |page|
        page.add_sitelinks([sitelinks])
        page.wbErrorDiv?.should be_true
        page.wbErrorDetailsLink?.should be_true
        page.wbErrorDetailsLink
        page.wbErrorDetailsDiv?.should be_true
        page.wbErrorDetailsDiv_element.text.should == edit_conflict_msg
        page.cancelSitelinkLink
      end
    end

    it "should complain about edit conflict when adding alias" do
      on_page(ItemPage) do |page|
        page.add_aliases([alias_a])
        page.wbErrorDiv?.should be_true
        page.wbErrorDetailsLink?.should be_true
        page.wbErrorDetailsLink
        page.wbErrorDetailsDiv?.should be_true
        page.wbErrorDetailsDiv_element.text.should == edit_conflict_msg
        page.cancelAliases
      end
    end

    it "should complain about edit conflict when changing description" do
      on_page(ItemPage) do |page|
        page.itemDescriptionSpan.should == description_user2
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

    it "should complain about edit conflict when editing label" do
      on_page(ItemPage) do |page|
        page.itemLabelSpan.should == label
        page.editLabelLink
        page.labelInputField = label_user1
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.wbErrorDiv?.should be_true
        page.wbErrorDetailsLink?.should be_true
        page.wbErrorDetailsLink
        page.wbErrorDetailsDiv?.should be_true
        page.wbErrorDetailsDiv_element.text.should == edit_conflict_msg
        page.cancelLabelLink
      end
    end

    it "should be possible to edit description after a reload" do
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_item_to_load
        page.itemDescriptionSpan.should == description_user2
        page.editDescriptionLink
        page.descriptionInputField = description_user1
        page.saveDescriptionLink
        ajax_wait
        page.wait_for_api_callback
        page.itemDescriptionSpan.should == description_user1
      end
    end

    it "should be possible to to edit label after a reload" do
      on_page(ItemPage) do |page|
        page.itemLabelSpan.should == label
        page.editLabelLink
        page.labelInputField = label_user1
        page.saveLabelLink
        ajax_wait
        page.wait_for_api_callback
        page.itemLabelSpan.should == label_user1
      end
    end

    it "should be possible to add alias after a reload" do
      on_page(ItemPage) do |page|
        page.add_aliases([alias_a])
        @browser.refresh
        page.wait_for_item_to_load
        page.wait_for_aliases_to_load
        page.get_nth_alias(1).text.should == alias_a
      end
    end

    it "should be possible to add sitelink after a reload" do
      on_page(ItemPage) do |page|
        page.add_sitelinks([sitelinks])
        page.wait_for_sitelinks_to_load
        page.get_number_of_sitelinks_from_counter.should == 1
      end
    end
  end

  after :all do
    # tear down: remove all sitelinks if there are some; logout
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_item_to_load
      page.wait_for_sitelinks_to_load
      page.remove_all_sitelinks
    end
    visit_page(LoginPage) do |page|
      page.logout_user
    end
  end

end
