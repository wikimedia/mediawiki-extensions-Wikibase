# Wikidata item tests
#
# License:: GNU GPL v2+
#
# testing loading time of huge entities

Feature: High performance

  Background:
    Given Entity Italy exists

  Scenario: Loading a huge entity
    Then get loading time of huge item page
