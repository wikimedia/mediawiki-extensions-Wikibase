# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# tests for entity selector

When(/^I press the (.+) key in the (.*)entity selector input field$/) do |key, second|
	mapping = {
		'ENTER' => :return,
		'ARROWDOWN' => :arrow_down,
		'ESC' => :escape
	}
	if mapping[key] then key = mapping[key] end
	if second == 'second ' then
		on(ItemPage).entity_selector_input2_element.send_keys key
	else
		on(ItemPage).entity_selector_input_element.send_keys key
	end
	sleep 1
end

When(/^I memorize the value of the (.*)entity selector input field$/) do |second|
	if second == 'second ' then
		@memorized = on(ItemPage).entity_selector_input2_element.attribute_value("value")
	else
		@memorized = on(ItemPage).entity_selector_input_element.attribute_value("value")
	end
end

Then /^Entity selector input element should be there$/ do
  on(ItemPage).entity_selector_input?.should be_true
end

Then /^Entity selector input element should not be there$/ do
  on(ItemPage).entity_selector_input?.should be_false
end
