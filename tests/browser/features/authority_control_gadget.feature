# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# feature definition for authority control gadget tests

@chrome @firefox @internet_explorer_10 @internet_explorer_11 @smoke
Feature: Authority control gadget test

# T221104
#  Scenario Outline: Check links created by gadget
#    When I navigate to item <item_id> with resource loader debug mode <debug_mode>
#      And The copyright warning has been dismissed
#      And Anonymous edit warnings are disabled
#    Then Authority control link should be active for claim 1 in group 1
#      And Authority control link should be active for claim 1 in group 2
#      And Authority control link should be active for claim 1 in group 3
#      And Authority control link should be active for claim 1 in group 4
#      And Authority control link should be active for claim 1 in group 5
#      And Authority control link should be active for claim 1 in group 6
#      And Authority control link should be active for claim 1 in group 7
#      And Authority control link should be active for claim 1 in group 8
#      And Authority control link should be active for claim 1 in group 9
#      And Authority control link of claim 1 in group 1 should link to www.openstreetmap.org
#      And Authority control link of claim 1 in group 2 should link to commons.wikimedia.org
#      And Authority control link of claim 1 in group 3 should link to tools.wmflabs.org/geohack/geohack.php
#      And Authority control link of claim 1 in group 4 should link to imdb.com
#      And Authority control link of claim 1 in group 5 should link to d-nb.info
#      And Authority control link of claim 1 in group 6 should link to viaf.org
#      And Authority control link of claim 1 in group 7 should link to www.dmoz.org
#      And Authority control link of claim 1 in group 8 should link to musicbrainz.org
#      And Authority control link of claim 1 in group 9 should link to www.freebase.com
#
#  @wikidata.beta.wmflabs.org
#  Examples:
#    | item_id | debug_mode |
#    | Q12480  | false |
#
#  Examples:
#    | item_id | debug_mode |
#    | Q12480  | true |
