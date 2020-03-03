# MediaWiki

## Content Handler

The ContentHandler facility is a mechanism for supporting arbitrary content types on wiki pages, instead of relying on wikitext for everything.
It was developed as part of the Wikidata project and is part of the MediaWiki core since version 1.21.

Documentation: https://www.mediawiki.org/wiki/Manual:ContentHandler

## Multi Content Revisions (MCR)

Multi-Content Revision support (also MCR) has changed the back-end of MediaWiki to support multiple content "slots" per revision, led by the MediaWiki and Wikidata teams.

The first usage of this was in the MediaInfo extension that brought Wikibase entities to https://commons.wikimedia.org file pages in a secondary slot.

## MediaWiki libs

Wikibase makes use of several MediaWiki "libs" that are conceptually standalone libraries (not depending on MediaWiki core itself),
but are controlled within the MediaWiki source repository and are not yet released as separate packages.

Use of these libs is not discouraged.
Other more entangled MediaWiki code should have minimal binding where possible.

These libraries can be found in includes/libs in MediaWiki core and include things such as:
 - rdbms - The database access layer
 - lockmanager
 - objectcache - WANObjectCache & BagOStuffs

## Features generally disabled for entities

This list may be incomplete, but exists as an example for disabled features and documentation of why they are disabled.

 - Direct page editing (action=edit)
 - History merges

Below are some extra details covering why some features are disabled.

### Page moves

Current "defaults" do not allow entities to be moved within an entity namespace or to another namespace.
This is enforced in the NamespaceIsMovable hook which will currently always return false when an entity is stored in the main slot.

#### History Merges

As a side affect of not allowing page moves, revision merges are also not possible.

Documentation: https://www.mediawiki.org/wiki/Manual:Merging_histories

Within MediaWiki core it is possible to merge the history of 2 or more pages.
The serialization of Wikibase entities normally contains the Entity ID within it in one or more places.
As a result merges are implemented differently in Wikibase, simply making new edits on the source and target moving the data,
but leaving the actual revision history on the respective pages.

In 2020 this topic came up with respect to the MediaInfo Wikibase extension. https://phabricator.wikimedia.org/T232087#5907216
