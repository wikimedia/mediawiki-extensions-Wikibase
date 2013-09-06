# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# steps for item aliases

Then /^Aliases UI should be there$/ do
  on(ItemPage) do |page|
    page.aliasesDiv?.should be_true
    page.aliasesTitle?.should be_true
  end
end

Then /^Aliases add button should be there$/ do
  sleep 5
  on(ItemPage).addAliases?.should be_true
end

Then /^Aliases add button should not be there$/ do
  on(ItemPage).addAliases?.should be_false
end

Then /^Aliases edit button should be there$/ do
  on(ItemPage).editAliases?.should be_true
end

Then /^Aliases edit button should not be there$/ do
  on(ItemPage).editAliases?.should be_false
end

Then /^Aliases list should be empty$/ do
  on(ItemPage).aliasesList?.should be_false
end

Then /^Aliases list should not be empty$/ do
  on(ItemPage).aliasesList?.should be_true
end
