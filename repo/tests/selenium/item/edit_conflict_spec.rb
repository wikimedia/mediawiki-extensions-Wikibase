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
edit_conflict_msg = "Edit conflict."
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

  context "check behavior on edit conflicts (descriptions)" do
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

  context "check normal behavior" do
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

  context "check behavior on edit conflicts (claims)" do
    prop_label = generate_random_string(10)
    prop_description = generate_random_string(20)
    prop_datatype = "Commons media file"
    cm_filename = "Air_France_A380_F-HPJA.jpg"
    statement_value_user1 = "Ellipse sign template.svg"
    statement_value_user2 = "Jaen - Mapa municipal.svg"
    statement_value_user1_changed = "Kreisbewegungen-Coppernicus-0.djvu"
    first_claim_guid = 0
    old_revid = 0
    it "should login as user 1, change claim and save revid" do
      visit_page(RepoLoginPage) do |page|
        page.logout_user
      end
      visit_page(NewPropertyPage) do |page|
        page.create_new_property(prop_label, prop_description, prop_datatype)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.add_statement(prop_label, cm_filename)
      end
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ORDINARY_USERNAME, WIKI_ORDINARY_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.statementValueInput_element.clear
        page.statementValueInput = statement_value_user1
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
        first_claim_guid = @browser.execute_script("return $('.wb-claimview').first().data('statementview').value().getGuid()");
        old_revid = @browser.execute_script("return wb.getRevisionStore().getClaimRevision('" + first_claim_guid.to_s() + "');")
        old_revid.should > 0
      end
    end
    it "should login as user 2, change claim value" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        page.editFirstStatement
        page.statementValueInput_element.clear
        page.statementValueInput = statement_value_user2
        page.saveStatement
        ajax_wait
        page.wait_for_statement_request_finished
        revid = @browser.execute_script("return wb.getRevisionStore().getClaimRevision('" + first_claim_guid.to_s() + "');")
        revid.should > old_revid
      end
    end
    it "should login as user 1 again, inject old revid & complain about edit conflict when changing claim value" do
      visit_page(RepoLoginPage) do |page|
        page.login_with(WIKI_ORDINARY_USERNAME, WIKI_ORDINARY_PASSWORD)
      end
      on_page(ItemPage) do |page|
        page.navigate_to_item
        page.wait_for_entity_to_load
        inject_old_revid = "wb.getRevisionStore().setClaimRevision(parseInt(" + old_revid.to_s() + "), '" + first_claim_guid.to_s() + "');"
        @browser.execute_script(inject_old_revid)
        injected_revid = @browser.execute_script("return wb.getRevisionStore().getClaimRevision('" + first_claim_guid.to_s() + "');")
        injected_revid.should == old_revid
        page.editFirstStatement
        page.statementValueInput_element.clear
        page.statementValueInput = statement_value_user1_changed
        page.saveStatement
        ajax_wait
        page.wbErrorDiv?.should be_true
        page.wbErrorDetailsLink?.should be_true
        page.wbErrorDetailsLink
        page.wbErrorDetailsDiv?.should be_true
        page.wbErrorDetailsDiv_element.text.should == edit_conflict_msg
        page.cancelStatement
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
