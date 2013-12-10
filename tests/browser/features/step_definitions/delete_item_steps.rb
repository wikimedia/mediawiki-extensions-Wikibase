Given(/^I am logged in$/) do
    visit_page(RepoLoginPage) do |page|
    page.login_with(WIKI_ADMIN_USERNAME, WIKI_ADMIN_PASSWORD)
  end
end

Given(/^I am on the item page$/) do
  on(ItemPage).should be_true
end

Given(/^item parameters are not empty$/) do
  page.descriptionInputField.should be_true
end

When(/^I click the item delete button$/) do
  pvisit_page(DeleteItemPage) 
end

When(/^click Delete page$/) do
  page.delete_item
end

Then(/^Page should be deleted$/) do
  page.searchText= "Action complete"
  page.searchSubmit
  page.searchResultDiv?.should be_true
  page.searchResults?.should be_false
  page.noResults?.should be_true
end
