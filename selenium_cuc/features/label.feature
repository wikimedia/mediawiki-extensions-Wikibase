# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for item label tests

Feature: Edit label

	Background:
		Given I am on an entity page

	Scenario: Check the button behavior
		Then Original label should be displayed
			And Label edit button should be there
			And Label cancel button should not be there
		When I click the label edit button
		Then Label input element should be there
			And Label input element should contain original label
			And Label cancel button should be there
		When I modify the label
		Then Label save button should be there
			And Label cancel button should be there
			And Label edit button should not be there
		When I click the label cancel button
		Then Original label should be displayed
			And Label edit button should be there
			And Label cancel button should not be there
