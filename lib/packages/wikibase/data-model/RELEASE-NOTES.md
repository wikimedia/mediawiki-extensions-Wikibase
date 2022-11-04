# Wikibase DataModel release notes

## Version 10.0.0 (TBD)

* Removed support for calling `Statement::addNewReference()` and `StatementList` constructor with a
  single array argument, which was deprecated in `Version 9.6.0 (2021-03-31)`. These should now be
  called with a variadic argument list.
* Added `__serialize()` and `__unserialize()` methods to the `EntityId` interface.
* Added native type hints to the `Statement` and `StatementList` classes
* Added `strict_types=1` to `Statement.php`, `StatementList.php`, and related test files

## Version 9.6.1 (2021-04-01)

* `Snak` now declares `getHash()` and `equals()` methods again,
  which it used to inherit from the `Hashable` and `Immutable` interfaces prior to version 9.6.0.
  (The methods were never removed from any specific classes,
  but since `Snak` is an interface, Phan started complaining that the methods were unknown.)

## Version 9.6.0 (2021-03-31)

* `ReferenceList::addNewReference()`, `Statement::addNewReference()` and the `StatementList` constructor
  supported being called with a variadic argument list, with a single array argument,
  or (in the case of `StatementList`) with a single `Traversable` argument.
  The latter two forms are now deprecated (though they still work);
  please update your code:
  for instance, change `->addNewReference( [ $x, $y ] )` to `->addNewReference( $x, $y )`,
  and `->addNewReference( $snaks )` to `->addNewReference( ...$snaks )`.
* `Statement`, `Reference`, `SnakList` and `Snak` no longer implement the `Hashable` and `Immutable` interfaces from `DataValues/DataValues`.
* Removed usages of the `Comparable` interface
* Made the library installable together with DataValues 3.x

## Version 9.5.1 (2020-06-03)

* Updated release notes

## Version 9.5.0 (2020-06-02)

* Added PHP 7.4 support

## Version 9.4.0 (2020-04-03)

* Added `getGuidPart` to `StatementGuid`

## Version 9.3.0 (2020-03-10)

* Raised minimum PHP version to 7.1
* Added `TermTypes` with term type constants
* Allow installing with wikimedia/assert 0.5.0

## Version 9.2.0 (2020-01-24)

* `TermList` now throws `InvalidArgumentException` when given non-iterable rather than failing silently
* `SiteLinkList` now throws `InvalidArgumentException` when given non-iterable rather than failing silently
* Slightly optimized `EntityId::isForeign`

## Version 9.1.0 (2019-01-24)

* Raised minimum PHP version to 7.0 or HHVM
* Redirecting an entity to itself now causes an exception

## Version 9.0.1 (2018-11-09)

* `Item` and `Property` now implement `ClearableEntity` again

## Version 9.0.0 (2018-11-01)

* Breaking change: `EntityDocument` no longer extends `ClearableEntity` (8.0.0 revert)
* The `TermList` constructor now takes any `iterable` instead of just `array`
* The `SiteLinkList` constructor now takes any `iterable` instead of just `array`
* Added `TermList::addAll`

## Version 8.0.0 (2018-08-03)

#### Breaking changes

* `Item::setId` and `Property::setId` no longer accept integers
* Removed `Item::getSiteLinks` and `Item::hasSiteLinks`
* Removed `HashArray`
* `SnakList` no longer extends `HashArray` and no longer has these public and protected methods:
	* `addElement`
	* `getByElementHash`
	* `getNewOffset`
	* `getObjectType`
	* `hasElement`
	* `hasElementHash`
	* `hasValidType`
	* `preSetElement`
	* `removeByElementHash`
	* `removeElement`
	* `setElement`
* Removed `WIKIBASE_DATAMODEL_VERSION` constant
* Added periods to the list of disallowed characters in `RepositoryNameAssert`
* `EntityDocument` now extends `ClearableEntity`

#### Other changes

* Added `StatementListProvidingEntity`
* Un-deprecated several sitelink related shortcuts from `Item`:
	* `addSiteLink`
	* `getSiteLink`
	* `hasLinkToSite`
	* `removeSiteLink`
* Installation together with DataValues 2.x is now supported

## Version 7.5.0 (2018-05-02)

* Introduce `ClearableEntity` interface.

## Version 7.4.1 (2018-05-02)

* Removed `clear` from `EntityDocument`. This was a compatibility break of the interface.

## Version 7.4.0 (2018-05-02)

* Added `clear` to `EntityDocument`

## Version 7.3.0 (2017-11-13)

* Performance optimizations on `EntityId`:
  	* Added protected `$repositoryName` and `$localPart` properties
  	* Added protected `extractRepositoryNameAndLocalPart`

## Version 7.2.0 (2017-10-23)

* Performance optimizations on methods critical for dump generation:
	* `DispatchingEntityIdParser::parse`
	* `SnakList::orderByProperty`

## Version 7.1.0 (2017-09-01)

* Changed `EntityIdValue::getArrayValue` to allow it handle foreign entity IDs and entity IDs that
  do not have a numeric representation.
* Fixed exception handling in `EntityIdValue` not always forwarding the full stack trace.
* Deprecated `EntityIdValue::newFromArray`
* Deprecated `StatementGuid::getSerialization`
* Improved documentation of `EntityDocument::isEmpty`
* Removed MediaWiki integration files

## Version 7.0.0 (2017-03-15)

This release adds support for custom entity types to `EntityIdValue`, and thus changes the hashes of
snaks, qualifiers, and references.

* Changed the internal `serialize()` format of several `EntityId` related classes. In all cases
  `unserialize()` still supports the previous format.
	* Serialization of `SnakObject` (includes `PropertyNoValueSnak` and `PropertySomeValueSnak`)
	  does not use numeric IDs any more
	* Serialization of `PropertyValueSnak` (includes `DerivedPropertyValueSnak`) does not use
	  numeric IDs any more
	* Serialization of `EntityIdValue` does not use numeric IDs any more
	* `EntityIdValue` can now serialize and unserialize `EntityId`s other than `ItemId` and
	  `PropertyId`
	* Minimized serialization of `ItemId` and `PropertyId` to not include the entity type any more

#### Other breaking changes

* Removed `FingerprintHolder`. Use `TermList::clear` and `AliasGroupList::clear` instead. `Item` and
  `Property` also still implement `setFingerprint`.
* Removed class aliases deprecated since 3.0:
	* `Wikibase\DataModel\Claim\Claim`
	* `Wikibase\DataModel\Claim\ClaimGuid`
	* `Wikibase\DataModel\StatementListProvider`
* Added a `SnakList` constructor that is not compatible with the `ArrayList` constructor any more,
  and does not accept null any more.
* Removed `HashArray::equals`, and `HashArray` does not implement `Comparable` any more
* Removed `HashArray::getHash`, and `HashArray` does not implement `Hashable` any more
* Removed `HashArray::rebuildIndices`
* Removed `HashArray::indicesAreUpToDate`
* Removed `HashArray::removeDuplicates`
* Removed `$acceptDuplicates` feature from `HashArray`

#### Additions

* Added `clear` to `TermList`, `AliasGroupList` and `StatementList`
* Added `newFromRepositoryAndNumber` to `ItemId` and `PropertyId`

#### Other changes

* Fixed `ReferenceList::addReference` sometimes moving existing references around
* Fixed exceptions in `DispatchingEntityIdParser` and `ItemIdParser` not forwarding the previous
  exception

## Version 6.3.1 (2016-11-30)

* `ItemId::getNumericId` and `PropertyId::getNumericId` no longer throw exceptions for foreign IDs

## Version 6.3.0 (2016-11-03)

* Added `RepositoryNameAssert` class

## Version 6.2.0 (2016-10-14)

* Raised minimum PHP version to 5.5
* Added basic support for foreign EntityIds
	* Added `isForeign`, `getRepository` and `getLocalPart` to `EntityId`
	* The constructor of `EntityId` was made public
	* Added static `EntityId::splitSerialization` and `EntityId::joinSerialization`
	* `getNumericId` throws an exception for foreign EntityIds
	* Added documentation for foreign EntityIds

## Version 6.1.0 (2016-07-15)

* Added optional index parameter to `Statement::addStatement`.
* Added `Int32EntityId` interface.
    * `ItemId` and `PropertyId` now implement `Int32EntityId`.
    * `ItemId` and `PropertyId` construction now fails for numbers larger than 2147483647.
* Added an `id` element containing the full ID string to the `EntityIdValue::getArrayValue`
  serialization.
* Fixed `ByPropertyIdArray` iterating the properties of non-traversable objects.

## Version 6.0.1 (2016-04-25)

* Fixed `ItemId` and `PropertyId` not rejecting strings with a newline at the end.

## Version 6.0.0 (2016-03-10)

This release removes the long deprecated Entity base class in favor of much more narrow interfaces.

#### Breaking changes

* Removed `Entity` class (deprecated since 1.0)
* `Item` and `Property` no longer extend `Entity`
    * Removed `getLabel`, `getDescription`, `getAliases`, `getAllAliases`,
      `setLabels`, `setDescriptions`, `addAliases`, `setAllAliases`,
      `removeLabel`, `removeDescription` and `removeAliases` methods
* `Item::getLabels` and `Property::getLabels` now return a `TermList`
* `Item::getDescriptions` and `Property::getDescriptions` now return a `TermList`
* Removed `clear` methods from `Item` and `Property`
* `StatementListProvider`, `LabelsProvider`, `DescriptionsProvider`, `AliasesProvider` and
  `FingerprintProvider` now give the guarantee to return objects by reference
* `TermList` and `AliasGroupList` no longer throw an `InvalidArgumentException` for invalid language codes.
    * `getByLanguage` throws an `OutOfBoundsException` instead.
    * `removeByLanguage` does nothing for invalid values.
    * `hasTermForLanguage` and `hasGroupForLanguage` return false instead.

#### Additions

* `Item` and `Property` now implement `LabelsProvider`, `DescriptionsProvider` and `AliasesProvider`
* Added `Item::getAliasGroups` and `Property::getAliasGroups`

## Version 5.1.0 (2016-03-08)

This release significantly reduces the memory footprint when entities are cloned.

* `Item::copy` and `Property::copy` do not clone immutable objects any more
* Deprecated `FingerprintHolder` and `StatementListHolder`

## Version 5.0.2 (2016-02-23)

* Fixed regression in `ReferenceList::addReference` and the constructor possibly adding too many objects

## Version 5.0.1 (2016-02-18)

* Fixed regression in `ReferenceList::removeReferenceHash` possibly removing too many objects
* `ReferenceList::unserialize` no longer calls the constructor

## Version 5.0.0 (2016-02-15)

This release removes the last remaining mentions of claims. Claims are still a concept in the mental
data model, but not modelled in code any more.

* Removed `Claims` class (deprecated since 1.0)
* Removed `getClaims` and `setClaims` methods from `Entity`, `Item` and `Property` (deprecated since 1.0)
* Removed `HashableObjectStorage` class (deprecated since 4.4)
* `ReferenceList` no longer derives from `SplObjectStorage`
    * Removed `addAll`, `attach`, `contains`, `detach`, `getHash`, `getInfo`, `removeAll`,
      `removeAllExcept` and `setInfo` methods
* `ReferenceList` no longer implements `ArrayAccess`
    * Removed `offsetExists`, `offsetGet`, `offsetSet` and `offsetUnset` methods
* `ReferenceList` no longer implements `Iterator`
    * Removed `current`, `key`, `next`, `rewind` and `valid` methods
* `ReferenceList` now implements `IteratorAggregate`
    * Added `getIterator` method
* Removed `ReferenceList::removeDuplicates`
* `ReferenceList::addReference` now throws an `InvalidArgumentException` for negative indices
* Added `EntityDocument::equals`, and `EntityDocument` now implements `Comparable`
* Added `EntityDocument::copy`
* Fixed `Property::clear` not clearing statements
* `TermList` now skips and removes empty terms
* Deprecated `ByPropertyIdArray`

## Version 4.4.0 (2016-01-20)

* Added `ItemIdParser`
* Added `ReferenceList::isEmpty`
* Added `ReferencedStatementFilter::FILTER_TYPE` constant
* Added `EntityRedirect::__toString`
* Deprecated `HashableObjectStorage`
* `SnakRole` enum is not an interface any more but a private class

## Version 4.3.0 (2015-09-02)

* Added `isEmpty` to `EntityDocument`

## Version 4.2.0 (2015-08-26)

* Added `EntityRedirect`
* Added `EntityIdParser` and `EntityIdParsingException`
* Added `BasicEntityIdParser`
* Added `DispatchingEntityIdParser`
* Removed no longer needed dependency on `diff/diff`

## Version 4.1.0 (2015-08-04)

* Added `StatementList::filter`
* Added `StatementFilter` and `ReferencedStatementFilter`
* Added `LabelsProvider`, `DescriptionsProvider` and `AliasesProvider`
* Added `FingerprintHolder`

## Version 4.0.0 (2015-07-28)

#### Breaking changes

The services that resided in this component have been moved to the new
Wikibase DataModel Services library. These symbols have been removed:

* `Entity::getDiff` and `Entity::patch`
* `EntityIdParser` and derivatives
* `EntityDiffer` and associated services
* `EntityPatcher` and associated services
* `EntityDiff` and derivatives
* `ItemLookup` and `ItemNotFoundException`
* `PropertyLookup` and `PropertyNotFoundException`
* `PropertyDataTypeLookup`
* `BestStatementsFinder`
* `ByPropertyIdGrouper`
* `StatementGuidParser` and alias `ClaimGuidParser`
* `StatementGuidParsingException` and alias `ClaimGuidParsingException`
* `StatementList::getBestStatementPerProperty`

#### Additions

* Added `DerivedPropertyValueSnak`

## Version 3.0.1 (2015-07-01)

* Fixed out of bounds bug in `SnakList::orderByProperty`

## Version 3.0.0 (2015-06-06)

#### Breaking changes

The concept of `Claim` is no longer modelled:

* The `Claim` class itself has been removed, though `Claim` is now a temporary alias for `Statement`
* `Claim::RANK_TRUTH` have been removed
* `Statement` no longer takes a `Claim` in its constructor
* `Statement::setClaim` and `Statement::getClaim` have been removed
* Removed `ClaimList`
* Removed `ClaimListAccess`
* Removed `addClaim`, `hasClaims` and `newClaim` from all entity classes

Phasing out of `Claims`:

* `Claims::addClaim` no longer supports setting an index
* Removed `Claims::getBestClaims`, use `StatementList::getBestStatements` instead
* Removed `Claims::getByRank` and `Claims::getByRanks`, use `StatementList::getByRank` instead
* Removed `Claims::getMainSnaks`, use `StatementList::getMainSnaks` instead
* Removed `Claims::getClaimsForProperty`, use `StatementList::getWithPropertyId` instead
* Removed `Claims::getHashes`
* Removed `Claims::getGuids`
* Removed `Claims::equals` (and `Claims` no longer implements `Comparable`)
* Removed `Claims::getHash` (and `Claims` no longer implements `Hashable`)
* Removed `Claims::hasClaim`
* Removed `Claims::isEmpty`, use `StatementList::isEmpty` instead
* Removed `Claims::indexOf`, use `StatementList::getFirstStatementWithGuid` or `StatementByGuidMap` instead
* Removed `Claims::removeClaim`

Other breaking changes:

* Removed `Snaks` interface, use `SnakList` instead
* Removed previously deprecated `Entity::getAllSnaks`, use `StatementList::getAllSnaks` instead
* Removed previously deprecated `EntityId::getPrefixedId`, use `EntityId::getSerialization` instead
* Removed previously deprecated `Property::newEmpty`, use `Property::newFromType` or `new Property()` instead
* Renamed `StatementList::getWithPropertyId` to `StatementList::getByPropertyId`
* Renamed `StatementList::getWithRank` to `StatementList::getByRank`
* Added `EntityDocument::setId`
* `Entity::setLabel` and `Entity::setDescription` no longer return anything
* `Reference` and `ReferenceList`s no longer can be instantiated with `null`

#### Additions

* Added `StatementByGuidMap`
* Added `StatementListHolder`
* Added `StatementList::getFirstStatementWithGuid`
* Added `StatementList::removeStatementsWithGuid`
* `ReferenceList::addNewReference` and `Statement::addNewReference` support an array of Snaks now
* Added PHPMD support

#### Deprecations

* Renamed `Claim\ClaimGuid` to `Statement\StatementGuid`, leaving a b/c alias in place
* Renamed `Claim\ClaimGuidParser` to `Statement\StatementGuidParser`, leaving a b/c alias in place
* Renamed `Claim\ClaimGuidParsingException` to `Statement\StatementGuidParsingException`, leaving a b/c alias in place
* Renamed `StatementListProvider` to `Statement\StatementListProvider`, leaving a b/c alias in place

#### Other changes

* `Item::setLabel`, `Item::setDescription` and `Item::setAliases` are no longer deprecated
* `Property::setLabel`, `Property::setDescription` and `Property::setAliases` are no longer deprecated

## Version 2.6.1 (2015-04-25)

* Allow installation together with Diff 2.x.

## Version 2.6.0 (2015-03-08)

* Added `Reference::isEmpty`
* Empty strings are now detected as invalid in the `SiteLink` constructor
* Empty References are now ignored when added to `ReferenceList`
* The `ReferenceList` constructor now throws an `InvalidArgumentException` when getting a non-iterable input
* The `SnakList` constructor now throws an `InvalidArgumentException` when getting a non-iterable input
* The `AliasGroup::equals` and `Term::equals` methods no longer incorrectly return true for fallback objects

## Version 2.5.0 (2014-01-20)

* Added `ItemLookup` and `PropertyLookup` interfaces
* Added `ItemNotFoundException`
* Added `AliasGroupList::getWithLanguages`
* Added `AliasGroupList::toTextArray`
* Added `ItemIdSet::getSerializations`
* Added `SiteLinkList::setNewSiteLink`
* Added `SiteLinkList::setSiteLink`
* Added `SiteLinkList::toArray`
* Added `TermList::getWithLanguages`
* Empty strings are now detected as invalid language codes in the term classes
* Made all `Fingerprint` constructor parameters optional
* Made all `Item` constructor parameters optional
* Made the `Property` constructor's fingerprint parameter nullable
* The `StatementList` constructor now accepts `Statement` objects in variable-length argument list format
* Deprecated `Fingerprint::newEmpty` in favour of `new Fingerprint()`
* Deprecated `Item::newEmpty` in favour of `new Item()`
* Added PHPCS support

## Version 2.4.1 (2014-11-26)

* Fixed `StatementList` not reindexing array keys

## Version 2.4.0 (2014-11-23)

* `Property` now implements the deprecated claim related methods defined in `Entity`
* Added `AliasGroupList::isEmpty`
* Added `StatementList::getBestStatements`
* Added `StatementList::getWithRank`
* Added `TermList::isEmpty`
* Added `AliasGroupFallback`
* Added `TermFallback`

## Version 2.3.0 (2014-11-18)

* Added `AliasGroupList::toArray`
* Added `StatementList::getMainSnaks`
* Added `StatementList::getWithPropertyId`
* `BestStatementsFinder::getBestStatementsForProperty` no longer throws an `OutOfBounds` exception

## Version 2.2.0 (2014-11-10)

* `Item` and `Property` now implement `StatementListProvider`
* Introduced the `StatementListProvider` interface for classes containing a `StatementList`
* Added rank comparison to `Statement::equals`

## Version 2.1.0 (2014-10-27)

* `ReferenceList` now implements `Serializable`
* Enhanced 32 bit compatibility for numeric ids

## Version 2.0.2 (2014-10-23)

* Fixed handling of numeric ids as string in `LegacyIdInterpreter` which was broken in 2.0.1.

## Version 2.0.1 (2014-10-23)

* Fixed last remaining HHVM issue (caused by calling `reset` on an `ArrayObject` subclass)
* `EntityIdValue::unserialize` now throws the correct type of exception
* Improved performance of `BasicEntityIdParser` and `LegacyIdInterpreter`

## Version 2.0.0 (2014-10-14)

#### Breaking changes

* Removed all class aliases
* Removed support for deserializing `EntityId` instances serialized with version 0.4 or earlier
* Removed `References` interface in favour of `ReferenceList`
* The `Statement` constructor no longer supports a `Snak` parameter

#### Additions

* Added `Statement::RANK_` enum
* Added `Statement::addNewReference`

#### Deprecations

* Deprecated `Claim::RANK_` enum in favour of `Statement::RANK_` enum
* Deprecated `Claim::getRank`

## Version 1.1.0 (2014-09-29)

#### Additions

* The `Property` constructor now accepts an optional `StatementList` parameter
* Added `Property::getStatements` and `Property::setStatements`
* Added `PropertyIdProvider` interface
* Added `ByPropertyIdGrouper`
* Added `BestStatementsFinder`
* Added `EntityPatcher` and `EntityPatcherStrategy`
* Added `StatementList::getAllSnaks` to use instead of `Entity::getAllSnaks`
* The `Statement` constructor now also accepts a `Claim` parameter
* Added `Statement::setClaim`
* The `Reference` constructor now accepts a `Snak` array
* Added `ReferenceList::addNewReference`

## Version 1.0.0 (2014-09-02)

#### Breaking changes

Changes in the `Entity` hierarchy:

* Changed the constructor signature of `Item`
* Changed the constructor signature of `Property`
* Removed `Entity::setClaims` (`Item::setClaims` has been retained)
* Removed `Entity::stub`
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

* `Item` now has an array of `Statement` rather than an array of `Claim`
* `Property` no longer has an array of `Claim`
* `Claim` and `Statement` no longer implement `Serializable`
* Protected method `Entity::entityToDiffArray` got renamed to `Entity::getDiffArray`
* Removed `Fingerprint::getAliases`
* Removed `EntityId::newFromPrefixedId`
* The constructor of `EntityId` is no longer public
* `Claims::getDiff` no longer takes a second optional parameter
* `Claims::getDiff` now throws an `UnexpectedValueException` rather than an `InvalidArgumentException`
* Removed these class aliases deprecated since 0.4:
`ItemObject`, `ReferenceObject`, `ClaimObject`, `StatementObject`
* `HashArray` and `SnakList` no longer take an optional parameter in `getHash`
* Calling `clear` on an `Item` will now cause its statements to be removed
* `SiteLinkList::addNewSiteLink` no longer returns a `SiteLinkList` instance
* Removed the global variable `evilDataValueMap`
* Removed `ClaimAggregate` interface, which is thus no longer implemented by `Entity`
* `HashableObjectStorage::getValueHash` no longer accepts a first optional parameter
* `MapHasher` and `MapValueHasher` are now package private
* Removed `Claims::getDiff`

#### Additions

* Added `ClaimList`
* Added `StatementList`
* Added `StatementListDiffer`
* Added `PropertyDataTypeLookup` and trivial implementation `InMemoryDataTypeLookup`
* Added `PropertyNotFoundException`
* Added `ItemDiffer` and `PropertyDiffer`
* Added `EntityDiffer` and `EntityDifferStrategy`
* Added `Statement::getClaim`
* Added `Item::getStatements`
* Added `Item::setStatements`

#### Deprecations

* Deprecated `Entity` (but not the derivatives)
* Deprecated `Claims`
* Deprecated `Entity::setId`
* Deprecated `Entity::newClaim`
* Deprecated `Entity::getAllSnaks`
* Deprecated `Entity::getDiff` in favour of `EntityDiffer` and more specific differs
* Deprecated `Item::getClaims` in favour of `Item::getStatements`
* Deprecated `Item::setClaims` in favour of `Item::setStatements`
* Deprecated `Item::hasClaims` in favour of `Item::getStatements()->count`
* Deprecated `Item::addClaim` in favour of `Item::getStatements()->add*`

#### Other changes

* Undeprecated passing an integer to `Item::setId` and `Property::setId`
* The FQN of `Statement` is now `Wikibase\DataModel\Statement\Statement`. The old FQN is deprecated.

## Version 0.9.1 (2014-08-26)

* Fixed error caused by redeclaration of getType in `Entity`, after it already got defined in `EntityDocument`

## Version 0.9.0 (2014-08-15)

* Changed the signatures of `setLabel`, `setDescription` and `setAliasGroup` in `Fingerprint`
* Added `hasLabel`, `hasDescription` and `hasAliasGroup` to `Fingerprint`

## Version 0.8.2 (2014-07-25)

* Added `EntityDocument` interface, which is implemented by `Entity`
* Added `LegacyIdInterpreter`
* Undeprecated `Entity::isEmpty`
* Undeprecated `Entity::clear`

## Version 0.8.1 (2014-06-06)

* Fixed fatal error when calling `Item::getSiteLinkList` on an `Item` right after constructing it

## Version 0.8.0 (2014-06-05)

#### Breaking changes

* `Item::removeSiteLink` no longer takes an optional second parameter and no longer returns a boolean
* Shallow clones of `Item` will now share the same list of site links
* `SiteLinkList` is now mutable

#### Additions

* `AliasGroupList::hasGroupForLanguage`
* `AliasGroupList::setAliasesForLanguage`
* `SiteLinkList::addSiteLink`
* `SiteLinkList::addNewSiteLink`
* `SiteLinkList::removeLinkWithSiteId`
* `SiteLinkList::isEmpty`
* `SiteLinkList::removeLinkWithSiteId`
* `Item::getSiteLinkList`
* `Item::setSiteLinkList`
* `TermList::setTextForLanguage`

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

* ~~`Entity::clear` (to be removed in 1.0)~~
* ~~`Entity::isEmpty` (to be removed in 1.0)~~
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

* Deprecated `Property::newEmpty` in favor of `Property::newFromType`
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

## Version 0.7.0 (2014-03-07)

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

## Version 0.6.0 (2013-12-23)

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

## Version 0.5.0 (2013-12-11)

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

## Version 0.4.0 (2013-06-17)

Initial release as Wikibase DataModel component.

## Version 0.1.0 (2012-11-01)

Initial release as part of Wikibase.
