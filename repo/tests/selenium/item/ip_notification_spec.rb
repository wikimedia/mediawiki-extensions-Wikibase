# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# Author:: H. Snater
# License:: GNU GPL v2+
#
# tests for ip warning for anonymous users

require 'spec_helper'

describe "Check functionality of ip notifications" do
  before :all do
    # setup
  end

  context "check functionality ip notifications" do
    it "should check notification on creating entity" do
      visit_page(RepoLoginPage) do |page|
        page.logout_user
      end
      visit_page(CreateItemPage) do |page|
        page.ipWarning?.should be_true
      end
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      visit_page(CreateItemPage) do |page|
        page.ipWarning?.should be_false
      end
    end
    it "should check notification on editing" do
      visit_page(RepoLoginPage) do |page|
        page.logout_user
      end
      visit_page(CreateItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      on_page(ItemPage) do |page|
        # label
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editLabelLink
        page.wait_for_mw_notification_shown
        page.mwNotificationContent?.should be_true
        # description
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editDescriptionLink
        page.wait_for_mw_notification_shown
        page.mwNotificationContent?.should be_true
        # aliases
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addAliases
        page.wait_for_mw_notification_shown
        page.mwNotificationContent?.should be_true
        # sitelinks
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addSitelinkLink
        page.wait_for_mw_notification_shown
        page.mwNotificationContent?.should be_true
      end
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        # label
        page.editLabelLink
        page.mwNotificationContent?.should be_false
        page.cancelLabelLink
        # description
        page.editDescriptionLink
        page.mwNotificationContent?.should be_false
        page.cancelDescriptionLink
        # aliases
        page.addAliases
        page.mwNotificationContent?.should be_false
        page.cancelAliases
        # sitelinks
        page.addSitelinkLink
        page.mwNotificationContent?.should be_false
        page.cancelSitelinkLink
      end
    end
    it "should check notification on editing statements" do
      visit_page(RepoLoginPage) do |page|
        page.logout_user
      end
      visit_page(NewPropertyPage) do |page|
        page.ipWarning?.should be_true
      end
      visit_page(CreateItemPage) do |page|
        page.create_new_item(generate_random_string(10), generate_random_string(20))
      end
      on_page(ItemPage) do |page|
        # statements
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.wait_for_mw_notification_shown
        page.mwNotificationContent?.should be_true
        page.cancelStatement
      end
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(ItemPage) do |page|
        # statements
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.addStatement
        page.mwNotificationContent?.should be_false
        page.cancelStatement
      end
    end
  end

  after :all do
    # tear down: logout
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end
end
