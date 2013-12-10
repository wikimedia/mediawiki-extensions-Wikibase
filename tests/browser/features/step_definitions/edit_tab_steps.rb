Given(/^I am on the item page$/) do
  on(ItemPage).should be_true
end

Then(/^the edit\-tab button should not be visible$/) do
  on_page(ItemPage) do |page|
    on(ItemPage).editTab?.should be_false
  end
end
