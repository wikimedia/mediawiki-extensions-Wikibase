# Wikibase DataModel release notes

## Version 1.0 (dev)

#### Breaking changes

Changes in the `Entity` hierarchy:

* Changed the constructor signature of `Item`
* Changed the constructor signature of `Property`
* Removed `Entity::setClaims` (`Item::setClaims` has been retained)
* Removed `Entity::clear`
* Removed `Entity::isEmpty`
* Removed `Entity::stub`
* Removed `Property::newEmpty`
* Removed `Entity::getIdFromClaimGuid`
* `Entity::removeLabel` no longer accepts an array of language codes
* `Entity::removeDescription` no longer accepts an array of language codes
* `Entity` no longer implements `Serializable`
* Protected method `Entity::patchSpecificFields` no longer has a second parameter
* `Entity::getFingerprint` is now returned by reference

Removal of `toArray` and `newFromArray`:

* Removed `Entity::toArray`, `Item::newFromArray` and `Property::newFromArray`
* Removed `Claim::toArray` and `Statement::toArray`
* Removed `Claim::newFromArray` and `Statement::newFromArray`
* Removed `ReferenceList::toArray` and `ReferenceList::newFromArray`
* Removed `toArray` from the `References` interface
* Removed `SiteLink::toArray` and `SiteLink::newFromArray`
* Removed `toArray` from the `Snak` and `Snaks` interfaces
* Removed `PropertyValueSnak::toArray`
* Removed `SnakList::toArray` and `SnakList::newFromArray`
* Removed `SnakObject::toArray` and `SnakObject::newFromArray`
* Removed `SnakObject::newFromType`

Other breaking changes:

* `Claim` and `Statement` no longer implement `Serializable`
* `SiteLinkList` is now mutable
* Protected method `Entity::entityToDiffArray` got renamed to `Entity::getDiffArray`
* Removed `Fingerprint::getAliases`
* Removed `EntityId::newFromPrefixedId`
* The constructor of `EntityId` is no longer public
* `Claims::getDiff` no longer takes a second optional parameter
* `Claims::getDiff` now throws an `UnexpectedValueException` rather than an `InvalidArgumentException`
* Removed these class aliases deprecated since 0.4:
`ItemObject`, `ReferenceObject`, `ClaimObject`, `StatementObject`
* Changed the signatures of `setLabel`, `setDescription` and `setAliasGroup` in `Fingerprint`
* `HashArray` and `SnakList` no longer take an optional parameter in `getHash`

#### Additions

* Added `LegacyIdInterpreter`
* Added `ClaimListDiffer`

## Version 0.8 (2014-06-05)

#### Breaking changes

* `Item::removeSiteLink` no longer takes an optional second parameter and no longer returns a boolean
* Shallow clones of `Item` will now share the same list of site links

#### Additions

* `AliasGroupList::hasGroupForLanguage`
* `SiteLinkList::addSiteLink`
* `SiteLinkList::addNewSiteLink`
* `SiteLinkList::removeLinkWithSiteId`
* `SiteLinkList::isEmpty`
* `SiteLinkList::removeLinkWithSiteId`
* `Item::getSiteLinkList`
* `Item::setSiteLinkList`
* `TermList::setTextForLanguage`
* `AliasGroupList::setAliasesForLanguage`

#### Deprecations

* `Item::addSiteLink`
* `Item::removeSiteLink`
* `Item::getSiteLinks`
* `Item::getSiteLink`
* `Item::hasLinkToSite`
* `Item::hasSiteLinks`

#### Improvements

* An empty `TermList` can now be constructed with no constructor arguments
* An empty `AliasGroupList` can now be constructed with no constructor arguments

## Version 0.7.4 (2014-04-24)

#### Additions

* Made these classes implement `Comparable`:
	* `TermList` 
	* `AliasGroupList`
	* `Fingerprint`
	* `SiteLink`
	* `SiteLinkList`
	* `Claim`
	* `Claims`
	* `Statement`
* Added methods to `Fingerprint`:
	* `getLabel`
	* `setLabel`
	* `removeLabel`
	* `setLabels`
	* `getDescription`
	* `setDescription`
	* `removeDescription`
	* `setDescriptions`
	* `getAliasGroup`
	* `setAliasGroup`
	* `removeAliasGroup`
	* `setAliasGroups`
	* `getAliasGroups`
	* `isEmpty`
* Added `ItemIdSet`

#### Deprecations

* `Entity::clear` (to be removed in 1.0)
* `Entity::isEmpty` (to be removed in 1.0)
* `Entity::stub` (to be removed in 1.0)
* `Fingerprint::getAliases` (in favour of `Fingerprint::getAliasGroups`)

#### Removals

* This library no longer uses the MediaWiki i18n system when MediaWiki is loaded.
No description will be shown as part of its entry on Special:Version.

## Version 0.7.3 (2014-04-11)

#### Additions

* Added `Wikibase\DataModel\Term` namespace with these constructs:
	* Term\AliasGroup
	* Term\AliasGroupList
	* Term\Fingerprint
	* Term\FingerprintProvider
	* Term\Term
	* Term\TermList
* Added `Entity::getFingerprint`
* Added `Entity::setFingerprint`

#### Deprecations

* Deprecated `Property::newEmpty`
* Deprecated old fingerprint related methods in `Entity`:
    * setLabel
    * setDescription
    * removeLabel
    * removeDescription
    * getAliases
    * getAllAliases
    * setAliases
    * addAliases
    * removeAliases
    * getDescriptions
    * getLabels
    * getDescription
    * getLabel
    * setLabels
    * setDescriptions
    * setAllAliases
* Deprecated `SnakList::newFromArray`
* Deprecated `Statement::newFromArray`
* Deprecated `Claim::newFromArray`
* Deprecated `ReferenceList::newFromArray`

## Version 0.7.2 (2014-03-13)

* Added Claims::getByRanks

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
* Removed Claims::getObjectType

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
