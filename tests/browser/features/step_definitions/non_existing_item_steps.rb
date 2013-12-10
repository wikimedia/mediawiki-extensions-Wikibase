# -*- encoding : utf-8 -*-
# Wikidata item tests
#
# License:: GNU GPL v2+
#
# steps for the non existing item functionality

Given(/^I am on an non existing item page$/) do
  visit_page(NonExistingItemPage)
end

Then(/^check if this page behaves correctly$/) do
  on_page(CreateItemPage) do |page|
  	should be on_page(NonExistingItemPage)
    page.createEntityLabelField.should be_true
    page.createEntityDescriptionField.should be_true
    page.firstHeading.should be_true
    page.firstHeading_element.text.should == ITEM_NAMESPACE + ITEM_ID_PREFIX + "xy"
  end
end

