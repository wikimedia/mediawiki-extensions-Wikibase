# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# step implementations for the authority control gadget

Then(/^Authority control link should be active for claim (.+) in group (.+)$/) do |claim_index, group_index|
  expect(on(ItemPage).statement_auth_control_link(group_index, claim_index).when_present).to be_present
end

Then(/^Authority control link of claim (.+) in group (.+) should link to (.+)$/) do |claim_index, group_index, url|
  expect(on(ItemPage).statement_auth_control_link(group_index, claim_index).when_present.attribute('href')).to include url
end
