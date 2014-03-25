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
      And I click the label edit button
    Then Label input element should be there

    Examples:
      | item          |
      | Italy         |