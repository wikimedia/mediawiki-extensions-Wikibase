# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# step implementations for snaktype selector

When(/^I click the snaktype selector of claim (\d+) in group (\d+)$/) do |claim_index, group_index|
  on(ItemPage).snaktype_selector(group_index, claim_index).when_visible.click
end

When(/^I select (.+) snaktype for claim (\d+) in group (\d+)$/) do |snaktype, claim_index, group_index|
  on(ItemPage).select_snaktype(group_index, claim_index, snaktype)
end

Then(/^Snaktype selector for claim (\d+) in group (\d+) should not be there$/) do |claim_index, group_index|
  expect(on(ItemPage).snaktype_selector(group_index, claim_index).when_not_present).not_to be_present
end

Then(/^Snaktype selector for claim (\d+) in group (\d+) should be there$/) do |claim_index, group_index|
  expect(on(ItemPage).snaktype_selector(group_index, claim_index).when_present).to be_present
end

Then(/^Snaktype selector menu should be visible$/) do
  expect(on(ItemPage).snaktype_selector_menu_element).to be_visible
end

Then(/^Snaktype selector menu should not be visible$/) do
  expect(on(ItemPage).snaktype_selector_menu_element).not_to be_visible
end

Then(/^Snaktype selector item for (.+) snaktype should be visible$/) do |snaktype|
  expect(on(ItemPage).snaktype_list[snaktype].item_element.when_visible).to be_visible
end

Then(/^Snaktype selector item for (.+) snaktype should not be visible$/) do |snaktype|
  expect(on(ItemPage).snaktype_list[snaktype].item_element.when_not_present).not_to be_present
end
