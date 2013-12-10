Given(/^I am logged in$/) do
  visit_page(RepoLoginPage) do |page|
    page.login_with(ENV["WB_REPO_USERNAME"], ENV["WB_REPO_PASSWORD"])
  end
end

Given(/^I am on the item page$/) do
  on(ItemPage).should be_true
end

Given(/^item parameters are not empty$/) do
  on(ItemPage).descriptionInputField.should be_true
end

When(/^I click the item delete button$/) do
  visit_page(DeleteItemPage)
end

When(/^click Delete page$/) do
  on(ItemPage).delete_item
end

Then(/^Page should be deleted$/) do
  on(ItemPage).should be_false
end
