# Wikidata item tests
#
# License:: GNU GPL v2+
#
# feature the functionality of a non existing item page

Feature: High performance

  Background:
    Given Entity Italy exists

  Scenario: Loading a huge entity
    Then get loading time of huge item page
