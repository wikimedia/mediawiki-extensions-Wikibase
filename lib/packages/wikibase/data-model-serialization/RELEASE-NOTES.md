# Wikibase DataModel Serialization release notes

## 1.4.0 (dev)

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
