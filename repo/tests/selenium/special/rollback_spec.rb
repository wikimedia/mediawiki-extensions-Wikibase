# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for rollback/revert

require 'spec_helper'

label = generate_random_string(10)
description = generate_random_string(20)
alias_a = generate_random_string(5)
sitelinks = [["en", "Vancouver"], ["de", "Vancouver"]]
sitelink_changed = "Vancouver Olympics"
changed = "_changed"

describe "Check revert/rollback" do
  before :all do
    # set up: create item, enter label, description and aliases & make some changes to item as another user
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
    visit_page(CreateItemPage) do |page|
      page.create_new_item(label, description)
      page.wait_for_entity_to_load
      page.add_aliases([alias_a])
      page.add_sitelinks(sitelinks)
    end
    visit_page(RepoLoginPage) do |page|
      page.login_with(WIKI_ORDINARY_USERNAME, WIKI_ORDINARY_PASSWORD)
    end
    on_page(ItemPage) do |page|
      page.navigate_to_item
      page.wait_for_entity_to_load
      page.change_label(label + changed)
      page.change_description(description + changed)
      page.editAliases
      page.aliasesInputFirst_element.clear
      page.aliasesInputEmpty= alias_a + changed
      page.saveAliases
      ajax_wait
      page.wait_for_api_callback
      page.sitelinksHeaderCode_element.click
      page.sitelinksHeaderCode_element.click
      page.editSitelinkLink
      page.pageInputFieldExistingSiteLink= sitelink_changed
      ajax_wait
      page.saveSitelinkLink
      ajax_wait
      page.wait_for_api_callback
    end
    visit_page(RepoLoginPage) do |page|
      page.logout_user
    end
  end

  context "rollback functionality test" do
    it "should login as admin and rollback changes by last user" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
      end
      on_page(HistoryPage) do |page|
        page.navigate_to_item_history
        page.rollbackLink_element.when_present.click
        page.returnToItemLink_element.when_present.click
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.entityLabelSpan.should == label
        page.entityDescriptionSpan.should == description
        page.get_nth_alias(1).text.should == alias_a
        page.englishSitelink_element.text.should == sitelinks[0][1]
      end
      visit_page(RepoLoginPage) do |page|
        page.logout_user
      end
    end
  end

  after :all do
    # tear down: remove all sitelinks & logout
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
