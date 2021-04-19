# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# step implementations for rank selector

When(/^I click the rank selector of claim (\d+) in group (\d+)$/) do |claim_index, group_index|
  on(ItemPage).rank_selector(group_index, claim_index).when_visible.click
end

When(/^I select (.+) rank for claim (\d+) in group (\d+)$/) do |rank, claim_index, group_index|
  on(ItemPage).select_rank(group_index, claim_index, rank)
end

Then(/^Rank selector for claim (\d+) in group (\d+) should not be there$/) do |claim_index, group_index|
  on(ItemPage) do |page|
    expect(page.rank_selector(group_index, claim_index).when_not_present).not_to be_present
    expect(page.rank_selector_disabled(group_index, claim_index).when_not_present).not_to be_present
  end
end

Then(/^Rank selector for claim (\d+) in group (\d+) should be there$/) do |claim_index, group_index|
  expect(on(ItemPage).rank_selector(group_index, claim_index).when_present).to be_present
end

Then(/^Rank selector for claim (\d+) in group (\d+) should be disabled/) do |claim_index, group_index|
  expect(on(ItemPage).rank_selector_disabled(group_index, claim_index).when_present).to be_present
end

Then(/^Rank selector menu should be visible$/) do
  expect(on(ItemPage).rank_selector_menu_element.when_visible).to be_visible
end

Then(/^Rank selector menu should not be visible$/) do
  expect(on(ItemPage).rank_selector_menu_element.when_not_present).not_to be_present
end

Then(/^Rank selector item for (.+) rank should be visible$/) do |rank|
  expect(on(ItemPage).rank_list[rank].item_element.when_visible).to be_visible
end

Then(/^Rank selector item for (.+) rank should not be visible$/) do |rank|
  expect(on(ItemPage).rank_list[rank].item_element.when_not_present).not_to be_present
end

Then(/^Indicated rank for claim (\d+) in group (\d+) should be (.+)/) do |claim_index, group_index, rank|
  expect(on(ItemPage).rank_indicator(group_index, claim_index, rank)).to be_present
end
