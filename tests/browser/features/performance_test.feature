# Wikidata item tests
#
# License:: GNU GPL v2+
#
# testing loading time of huge entities

@performance_testing
Feature: High performance

  Background:
    Given Entity Italy defined in data/q38.json exists
      #And Entity Douglas Adams defined in data/q42.json exists # violates length constraint
      #And Entity Barack Obama defined in data/q76.json exists # violates length constraint
      #And Entity Zürich defined in data/q72.json exists # violates length constraint

  Scenario Outline: Loading a huge entity
    Then get loading time of <page>

    Examples:
      | page          |
      | Italy         |
      #| Douglas Adams |
      #| Barack Obama  |
      #| Zürich        |