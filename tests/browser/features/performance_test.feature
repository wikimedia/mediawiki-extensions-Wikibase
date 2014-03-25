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
    When I load the huge item <item>
    Then Label edit button should be there
      And Javascript UI should be initialized

    Examples:
      | item          |
      | Italy         |