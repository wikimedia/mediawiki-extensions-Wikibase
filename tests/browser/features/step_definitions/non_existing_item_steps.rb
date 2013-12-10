Given(/^I am on an non existing item page$/) do
  visit_page(NonExistingItemPage) do |page|
    page.firstHeading.should be_true
    page.firstHeading_element.text.should == ITEM_NAMESPACE + ITEM_ID_PREFIX + "xy"
    page.specialCreateNewItemLink?.should be_true
    page.specialCreateNewItemLink
  end
end

Then(/^check if this page behaves correctly$/) do
  on_page(CreateItemPage) do |page|
    page.createEntityLabelField.should be_true
    page.createEntityDescriptionField.should be_true
  end
end

