# Wikibase DataModel Services release notes

## Version 2.1.0 (dev)

* Added `EntityAccessLimitException`
* `RestrictedEntityLookup` now throws `EntityAccessLimitException` (non breaking change)

## Version 2.0.1 (2015-09-10)

* Fixed uncaught exception in EntityIdLabelFormatter::formatEntityId ([T112003](https://phabricator.wikimedia.org/T112003))

## Version 2.0.0 (2015-09-02)

Moved `EntityIdParser` back to Wikibase DataModel:

* Removed `EntityIdParser`
* Removed `EntityIdParsingException`
* Removed `BasicEntityIdParser`
* Removed `DispatchingEntityIdParser`

Changed all Lookup contracts:

* All lookups now return null when there is no value found as a result of the lookup:
  * `EntityRetrievingTermLookup` returns null instead of throwing `OutOfBoundsException`.
  * `LanguageLabelDescriptionLookup` returns null instead of throwing `OutOfBoundsException`.
  * `ItemLookup` implementations should return null instead of throwing `ItemNotFoundException`.
  * `LabelDescriptionLookup` implementations should return null instead of `OutOfBoundsException`.
  * `PropertyLookup` implementations should return null instead of `PropertyNotFoundException`.
  * `TermLookup` implementations should return null instead of `OutOfBoundsException`.
* All lookups now throw exceptions in exceptional circumstances:
  * `EntityLookup` implementations should throw `EntityLookupException` instead of returning null.
  * `EntityRedirectLookup` implementations should throw `EntityRedirectLookupException` instead of returning false.
  * `EntityRetrievingDataTypeLookup` throws `PropertyDataTypeLookupException` instead of `PropertyNotFoundException`.
  * `EntityRetrievingTermLookup` throws `TermLookupException` instead of `OutOfBoundsException`.
  * `InMemoryDataTypeLookup` throws `PropertyDataTypeLookupException` instead of `PropertyNotFoundException`.
  * `ItemLookup` implementations should throw `ItemLookupException` instead of `ItemNotFoundException`.
  * `LabelDescriptionLookup` implementations should throw `LabelDescriptionLookupException` instead of `OutOfBoundsException`.
  * `LanguageLabelDescriptionLookup` throws `LabelDescriptionLookupException` instead of `OutOfBoundsException`.
  * `PropertyDataTypeLookup` implementations should throw `PropertyDataTypeLookupException` instead of `PropertyNotFoundException`.
  * `PropertyLookup` implementations should throw `PropertyLookupException` instead of `PropertyNotFoundException`.
  * `TermLookup` implementations should throw `TermLookupException` instead of `OutOfBoundsException`.
* Removed `Lookup\ItemNotFoundException`
* Removed `Lookup\PropertyNotFoundException`
* Added `Lookup\EntityLookupException`
* Added `Lookup\EntityRedirectLookupException`
* Added `Lookup\LabelDescriptionLookupException`
* Added `Lookup\TermLookupException`
* Added `Lookup\ItemLookupException`
* Added `Lookup\PropertyLookupException`

Moved over various classes and interfaces from Wikibase Lib:

* Added `Lookup\RedirectResolvingEntityLookup`
* Added `Lookup\RestrictedEntityLookup`
* Added `Diff\EntityTypeAwareDiffOpFactory` (previously called WikibaseDiffOpFactory in Lib)

Other additions:

* Added `Lookup\InMemoryEntityLookup`

## Version 1.1.0 (2015-08-10)

Moved over various classes and interfaces from Wikibase Lib:

* `DataValue\ValuesFinder`
* `Entity\EntityPrefetcher`
* `Entity\EntityRedirectResolvingDecorator`
* `Entity\NullEntityPrefetcher`
* `EntityId\EntityIdFormatter`
* `EntityId\EntityIdLabelFormatter`
* `EntityId\EscapingEntityIdFormatter`
* `EntityId\PlainEntityIdFormatter`
* `EntityId\SuffixEntityIdParser`
* `Lookup\EntityLookup`
* `Lookup\EntityRedirectLookup`
* `Lookup\EntityRetrievingDataTypeLookup`
* `Lookup\EntityRetrievingTermLookup`
* `Lookup\LabelDescriptionLookup`
* `Lookup\LanguageLabelDescriptionLookup`
* `Lookup\TermLookup`
* `Statement\StatementGuidValidator`
* `Term\PropertyLabelResolver`
* `Term\TermBuffer`

These have not been changed apart from now residing in a different namespace, and in some
cases using dependencies that have similarly been moved.

* Added `Entity\UnresolvedRedirectException`, similar to the one in Wikibase Lib, though without revision info

## Version 1.0.0 (2015-07-28)

Initial release containing

* Entity diffing and patching functionality in `Services\Diff`
* `EntityIdParser` and basic implementations in `Services\EntityId`
* `ItemLookup`, `PropertyLookup` and `PropertyDataTypeLookup` interfaces
* Statement GUID parser and generators in `Services\Statement`
* `ByPropertyIdGrouper`
