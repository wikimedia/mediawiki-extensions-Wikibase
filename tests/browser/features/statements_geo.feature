# Wikidata UI tests
#
# Author:: Christoph Fischer (christoph.fischer@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for geo type statements tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @local_config @test.wikidata.org @wikidata.beta.wmflabs.org
Feature: Using geo properties in statements

  Background:
    Given I have the following properties with datatype:
      | geoprop | globe-coordinate |
      And I am not logged in to the repo

# T221104
#  @ui_only
#  Scenario: Geo UI should work properly
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property geoprop
#      And I enter 1,1 in the claim value input field
#    Then Statement save button should be there
#      And Statement cancel button should be there
#      And InputExtender preview should be there
#      And Geo precision chooser should be there
#
#  @ui_only
#  Scenario Outline: Check geo UI for invalid values
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property geoprop
#      And I enter <value> in the claim value input field
#    Then Statement save button should not be there
#      And Statement cancel button should be there
#
#  Examples:
#    | value |
#    | astring |
#    | 1 11 199 9 |
#    | 1 |
#    | 1:1 |
#
#  @ui_only
#  Scenario Outline: Geo parser in the preview and precision detection should work properly
#    Given I am on an item page
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    When I click the statement add button
#      And I select the claim property geoprop
#      And I enter <value> in the claim value input field
#    Then <preview> should be displayed in the InputExtender preview
#      And <precision> should be the geo precision setting
#      And Statement save button should be there
#      And Statement cancel button should be there
#
#  Examples:
#    | value | preview | precision |
#    | 1 1 | 1°N, 1°E | ±1° |
#    | 1 S 1 W | 1°S, 1°W | ±1° |
#    | 52°29'53"N, 13°22'51"E | 52°29'53"N, 13°22'51"E | to an arcsecond |
#    | 52°29'N, 13°22'E | 52°29'N, 13°22'E | to an arcminute |
#    | 42.1538, 8.5731 | 42°9'13.7"N, 8°34'23.2"E | ±0.0001° |
#    | 42° 09.231' N 008° 34.386' E | 42°9'13.86"N, 8°34'23.16"E | to 1/100 of an arcsecond |
#
#  @modify_entity
#  Scenario: Adding a statement of type geo
#    Given I am logged in to the repo
#      And I am on an item page
#      And The copyright warning has been dismissed
#    When I click the statement add button
#      And I select the claim property geoprop
#      And I enter 52°29'53.9"N, 13°22'51.8"E in the claim value input field
#      And I click the statement save button
#    Then Statement string value of claim 1 in group 1 should be 52°29'53.9"N, 13°22'51.8"E
#      And Statement name of group 1 should be the label of geoprop
#      And Statement save button should not be there
#      And Statement cancel button should not be there
#      And Statement edit button for claim 1 in group 1 should be there
#
#  @modify_entity
#  Scenario: Adding a statement of type geo and reload page
#    Given I am logged in to the repo
#      And I am on an item page
#      And The copyright warning has been dismissed
#    When I click the statement add button
#      And I select the claim property geoprop
#      And I enter 52°29'53.9"N, 13°22'51.8"E in the claim value input field
#      And I click the statement save button
#      And I reload the page
#    Then Statement string value of claim 1 in group 1 should be 52°29'53.9"N, 13°22'51.8"E
#      And Statement name of group 1 should be the label of geoprop
#      And Statement save button should not be there
#      And Statement cancel button should not be there
#      And Statement edit button for claim 1 in group 1 should be there
