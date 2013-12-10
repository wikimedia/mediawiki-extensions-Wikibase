
When(/^I click the item delete button$/) do
  on(ItemPage).delete_element.click
end

Then(/^Page should be deleted$/) do
  item_element.should_not exist
end
