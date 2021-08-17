# Wikibase InternalSerialization release notes

### 2.10.0 (2020-01-24)

* `EntityDeserializer` now also implements `DispatchableDeserializer`
* Raised minimum PHP version to 7.0 (^7.0)

### 2.9.0 (2018-11-06)

* Added compatibility with Wikibase DataModel 9.x

### 2.8.0 (2018-08-07)

* Added compatibility with Wikibase DataModel 8.x
* Dropped compatibility with Wikibase DataModel 4.x
* Raised minimum PHP version to 5.6

### 2.7.0 (2017-10-26)

* Added compatibility with Serialization 4.x

### 2.6.0 (2017-09-18)

* Added compatibility with DataValues Common 0.4, Number 0.9, and Time 0.8

### 2.5.0 (2017-08-30)

* Added compatibility with DataValues Geo 2.x
* Removed MediaWiki integration files
* Updated minimal required PHP version from 5.3 to 5.5.9

### 2.4.0 (2017-03-16)

* Added compatibility with Wikibase DataModel 7.x

### 2.3.0 (2016-03-14)

* Added compatibility with Wikibase DataModel 6.x

### 2.2.0 (2016-03-03)

* `DeserializerFactory` constructor now optionally takes a `DispatchableDeserializer` as third argument

### 2.1.0 (2016-02-18)

* Added compatibility with Wikibase DataModel 5.x
* Added compatibility with DataValues Common 0.3

### 2.0.0 (2015-08-31)

* Dropped dependence on Wikibase DataModel Services

### 1.5.0 (2015-07-29)

* Added compatibility with Wikibase DataModel 4.x
* Removed compatibility with Wikibase DataModel 3.x

### 1.4.0 (2015-06-12)

* Added compatibility with DataModel 3.x
* Deprecated `LegacyDeserializerFactory::newClaimDeserializer` in favour of `LegacyDeserializerFactory::newStatementDeserializer`
* Deprecated `DeserializerFactory::newClaimDeserializer` in favour of `DeserializerFactory::newStatementDeserializer`
* Added support for showing the component version when loaded via MediaWiki
* Added PHPMD and PHPCS support

### 1.3.1 (2015-01-06)

* Installation together with DataValues Geo 1.x is now supported

### 1.3.0 (2014-10-15)

* Added compatibility with DataModel 2.x

### 1.2.1 (2014-09-11)
* Added LegacyStatementDeserializer
* Adding normalization in LegacyItemDeserializer to handle Claims (e.g. no ranks),
  on Items for more robustness with old serialization formats.

### 1.2.0 (2014-09-02)

* Changed used DataModel version to 1.x.

### 1.1.0 (2014-06-16)

* Added `DeserializerFactory::newClaimDeserializer`
* The Deserializer for snaks now constructs `UnDeserializableValue` objects for invalid data values

### 1.0.0 (2014-05-27)

Initial release with these features:

* Serializers for the main Wikibase DataModel (1.0) objects
* Deserializers for the main Wikibase DataModel (1.0) objects
