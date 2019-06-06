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
  on_page(NonExistingItemPage) do |page|
    expect(page.first_heading?).to be true
    expect(page.first_heading_element.text).to be == ENV['ITEM_NAMESPACE'] + ENV['ITEM_ID_PREFIX'] + 'xy'
  end
end
