# Wikibase DataModel release notes

## Version 0.7.1 (2014-03-12)

* Removed DataValues Geo, DataValues Time and DataValues Number from the dependency list.
They where no longer needed.

## Version 0.7 (2014-03-07)

#### Additions

* Added TypedSnak value object
* Added SiteLinkList value object
* Added Claims::getBestClaims
* Added Claims::getByRank

#### Improvements

* The PHPUnit bootstrap file now works again on Windows
* Changed class loading from PSR-0 to PSR-4

#### Deprecations

* Deprecated SiteLink::toArray(), SiteLink::newFromArray(), SiteLink::getBadgesFromArray()

#### Removals

* Removed PropertySnak interface
* Claims::getObjectType removed

## Version 0.6 (2013-12-23)

#### Improvements

* Wikibase DataModel now uses the "new" DataValues components. This means binding to other code has
decreased and several design issues have been tackled.
* Wikibase DataModel is now PSR-0 compliant.

#### Deprecations

* All classes and interfaces not yet in the Wikibase\DataModel namespace got moved. The old names
remain as aliases, and should be considered as deprecated.
* SimpleSiteLink was renamed to SiteLink. The old name remains as deprecated alias.
* Item::addSimpleSiteLink and Item::getSimpleSiteLinks where renamed to Item::adSiteLink and
Item::getSiteLinks. The old names remains as deprecated aliases.

#### Removals

* Entity::getTerms was removed, as it returned objects of type Term, which is defined by a component
Wikibase DataModel does not depend upon.

## Version 0.5 (2013-12-11)

Note that this list is incomplete. In particular, not all breaking changes are listed.

#### Additions

* Added ItemId and PropertyId classes.
* Added BasicEntityIdParser that allows for parsing of serializations of entity ids defined
by Wikibase DataModel.
* Added ClaimGuid and ClaimGuidParser.

#### Improvements

* EntityId no longer is a DataValue. A new EntityIdValue was added to function as a DataValue
representing the identity of an entity.

#### Removals

* ObjectComparer has been removed from the public namespace.
* SnakFactory has been moved out of this component.

#### Deprecations

* Constructing an EntityId (rather then one of its derivatives) is now deprecated.
* Wikibase\EntityId has been renamed to Wikibase\DataModel\Entity\EntityId. The old name is deprecated.

## Version 0.4 (2013-06-17)

Initial release as Wikibase DataModel component.

## Version 0.1 (2012-11-01)

Initial release as part of Wikibase.
