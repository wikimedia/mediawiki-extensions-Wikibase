# Wikibase DataModel Services release notes

## Version 2.0 (Development)

Moved over various classes and interfaces from Wikibase Lib:

* Added `Lookup\RedirectResolvingEntityLookup`
* Added `Lookup\RestrictedEntityLookup`
* Added `Diff\EntityTypeAwareDiffOpFactory`, Was called WikibaseDiffOpFactory in Lib

Changed all Lookup contracts:

* All lookups now return null when there is no value found as a result of the lookup.
* All lookups now throw exceptions in exceptional circumstances.

* Added `Lookup\EntityLookupException`
* Added `Lookup\EntityRedirectLookupException`
* Added `Lookup\LabelDescriptionLookupException`
* Added `Lookup\TermLookupException`
* Added `Lookup\ItemLookupException`
* Added `Lookup\PropertyLookupException`
* Removed `Lookup\ItemNotFoundException`
* Removed `Lookup\PropertyNotFoundException`

## Version 1.1 (2015-08-10)

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

## Version 1.0 (2015-07-28)

Initial release containing

* Entity diffing and patching functionality in `Services\Diff`
* `EntityIdParser` and basic implementations in `Services\EntityId`
* `ItemLookup`, `PropertyLookup` and `PropertyDataTypeLookup` interfaces
* Statement GUID parser and generators in `Services\Statement`
* `ByPropertyIdGrouper`
