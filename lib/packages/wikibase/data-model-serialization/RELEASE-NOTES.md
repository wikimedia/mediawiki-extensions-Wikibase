# Wikibase DataModel Serialization release notes

## 2.10.0 (development)

* Require Wikibase DataModel 8.x or 9.x
* Raised minimum PHP version to 7.0 (^7.0)
* Moved (De)SerializerFactories into (De)Serializer Namespaces

## 2.9.1 (2018-12-14)

* Apply ID prefix mapping in `SnakDeserializer`, like `EntityIdValueParser` does.

## 2.9.0 (2018-11-06)

* Added compatibility with Wikibase DataModel 9.x

## 2.8.0 (2018-08-07)

* Added compatibility with Wikibase DataModel 8.x

## 2.7.0 (2018-03-29)

* Fixed `AliasGroupListDeserializer` and `TermListDeserializer` misbehaving when confronted with
  language codes exclusively made of digits.
* Fixed `SnakDeserializer` possibly accessing non-existing array elements.
* Improved documentation of `SerializerFactory::newEntitySerializer` as well as
  `DeserializerFactory::newEntityDeserializer`.
* Added compatibility with Serialization 4.x
* Improved forward-compatibility with PHPUnit 6
* Note: The `SnakDeserializer` constructor changed, but since it's declared package private, this
  shouldn't affect anyone.

## 2.6.0 (2017-09-18)

* Added compatibility with DataValues Number 0.9

## 2.5.0 (2017-08-30)

* Removed MediaWiki integration files
* Added compatibility with DataValues Geo 2.x
* Deprecated `SerializerFactory` options
  `OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH`,
  `OPTION_SERIALIZE_QUALIFIER_SNAKS_WITHOUT_HASH` and
  `OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH`.
* Added `SerializerFactory::OPTION_SERIALIZE_SNAKS_WITHOUT_HASH`.

## 2.4.0 (2017-03-16)

* Added compatibility with Wikibase DataModel 7.x

## 2.3.0 (2017-02-15)

* Improved performance of `StatementDeserializer` as well as other deserializers
* Improved type safety throughout the code
* Dropped support for PHP 5.3 and PHP 5.4

## 2.2.0 (2016-03-11)

* Added compatibility with Wikibase DataModel 6.x

## 2.1.0 (2016-02-18)

* Added `newItemSerializer` and `newPropertySerializer` to `SerializerFactory`
* Added `newItemDeserializer` and `newPropertyDeserializer` to `DeserializerFactory`
* Added compatibility with Wikibase DataModel 5.x
* Added compatibility with DataValues Common 0.3

## 2.0.0 (2015-08-30)

* Dropped dependency on Wikibase DataModel Services
* Removed `newClaimSerializer`, `newClaimsSerializer` and `newSnaksSerializer` from `SerializerFactory`
* Removed `newClaimDeserializer`, `newClaimsDeserializer` and `newSnaksDeserializer` from `DeserializerFactory`

## 1.9.1 (2015-08-27)

* Revert of breaking changes, will be added in 2.0 again

## 1.9.0 (2015-08-26)

* Dropped dependency on Wikibase DataModel Services

## 1.8.0 (2015-07-28)

* Added compatibility with Wikibase DataModel 4.x
* Removed compatibility with Wikibase DataModel 3.x

## 1.7.0 (2015-07-23)

* Added `SerializerFactory` option `OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH`
* Added `SerializerFactory` option `OPTION_SERIALIZE_QUALIFIER_SNAKS_WITHOUT_HASH`
* Added `SerializerFactory` option `OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH`
* Added `$serializeSnaksWithHash` parameter to `newSnakListSerializer`, the default is b/c
* Added `$serializeWithHash` parameter to `newSnakSerializer`, the default is b/c
* Added `$serializeWithHash` parameter to `newTypedSnakSerializer`, the default is b/c
* Added support for deserializing ungrouped SnakLists and StatementLists

## 1.6.0 (2015-07-20)

* Added `newAliasGroupSerializer` to `SerializerFactory`

## 1.5.0 (2015-07-01)

* Added `newTermSerializer` to `SerializerFactory`
* Added `newTermListSerializer` to `SerializerFactory`
* Added `newAliasGroupListSerializer` to `SerializerFactory`
* Added `newTermDeserializer` to `DeserializerFactory`
* Added `newTermListDeserializer` to `DeserializerFactory`
* Added `newAliasGroupListDeserializer` to `DeserializerFactory`
* Deprecated `SerializerFactory::newClaimsSerializer` in favour of `SerializerFactory::newStatementListSerializer`
* Deprecated `DeserializerFactory::newClaimsDeserializer` in favour of `DeserializerFactory::newStatementListDeserializer`

## 1.4.0 (2015-06-08)

* Added compatibility with Wikibase DataModel 3.x
* Removed compatibility with Wikibase DataModel 2.x
* Renamed `SerializerFactory::newClaimSerializer` to `SerializerFactory::newStatementSerializer`, leaving a b/c alias
* Renamed `SerializerFactory::newSnaksSerializer` to `SerializerFactory::newSnakListSerializer`, leaving a b/c alias
* Renamed `DeserializerFactory::newClaimDeserializer` to `DeserializerFactory::newStatementDeserializer`, leaving a b/c alias
* Renamed `DeserializerFactory::newSnaksDeserializer` to `DeserializerFactory::newSnakListDeserializer`, leaving a b/c alias
* Added `SerializerFactory::newStatementListSerializer`
* Added `DeserializerFactory::newStatementListDeserializer`
* Added support for showing the component version when loaded via MediaWiki

## 1.3.0 (2015-01-28)

* `FingerprintSerializer` now supports serializing `AliasGroupFallback`s and `TermFallback`s
* `EntityDeserializer` now fails if the given serialization contains a term or alias that was either
  the result of a fallback or transliteration
* Added `newTypedSnakSerializer` to `SerializerFactory`

## 1.2.0 (2014-10-15)

* Compatibility with DataModel 2.x added
* Support statements on properties
* Add option to serialize maps as objects instead of arrays so as to be able to
  differentiate empty maps from empty lists

## 1.1.1 (2014-09-09)

* Use UnDeserializable error from serialization in SnakDeserializer

## 1.1.0 (2014-09-02)

* Compatibility with DataModel 1.x was added
* DataModel 1.x is now required

## 1.0.3 (2014-07-28)

* Hashes are now ignored by the SnakDeserializer
* Compatibility with Wikibase DataModel 1.x was improved

## 1.0.2 (2014-07-21)

* Fixed issue where invalid snaks-order elements in reference serialization caused an error rather
 than a deserialization exception
* Hashes are now ignored by the ReferenceDeserializer

## 1.0.1 (2014-06-16)

* The Deserializer for snaks now constructs UnDeserializableValue objects for invalid data values

## 1.0 (2014-05-27)

* Usage of DataModel 0.7.x rather than 0.6.x.
* Usage of Serialization ~3.1 rather than ~2.1.
* Snaks now always have a 'hash' element in their serialization
* Added `snaks-order` support to `ReferenceSerializer` and `ReferenceDeserializer`
* Added `qualifiers-order` support to `ClaimDeserializer`
* Added `TypedSnakSerializer`
* Added hash validation for references and snaks
* Added additional tests to ensure old serializations can still be deserialized

## 0.1 (2014-02-22)

Initial release with these features:

* Serializers for the main Wikibase DataModel (0.6) objects
* Deserializers for the main Wikibase DataModel (0.6) objects
