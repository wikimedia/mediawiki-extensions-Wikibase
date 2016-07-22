# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# basic steps for properties

Then(/^Property datatype heading should be there$/) do
  expect(on(PropertyPage).property_datatype_heading_element.when_visible).to be_visible
end

Then(/^Property datatype should display (.*)$/) do |datatype|
  expect(on(PropertyPage).property_datatype_element.when_visible.text).to be == datatype
end
