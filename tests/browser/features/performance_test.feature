# Wikidata item tests
#
# License:: GNU GPL v2+
#
# testing loading time of huge entities

@performance_testing
Feature: High performance

  Background:
    Given Entity Italy defined in data/q38.json exists

  Scenario Outline: Loading a huge entity
    Then get loading time of <page>

    Examples:
      | page          |
      | Italy         |