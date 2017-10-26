# Wikibase Internal Serialization

Library containing serializers and deserializers for the data access layer of [Wikibase](http://wikiba.se/) Repository.

[![Build Status](https://secure.travis-ci.org/wmde/WikibaseInternalSerialization.png?branch=master)](http://travis-ci.org/wmde/WikibaseInternalSerialization)
[![Code Coverage](https://scrutinizer-ci.com/g/wmde/WikibaseInternalSerialization/badges/coverage.png?s=b65f644a99b93ed3aa1a34e45efbccad798d168c)](https://scrutinizer-ci.com/g/wmde/WikibaseInternalSerialization/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/wmde/WikibaseInternalSerialization/badges/quality-score.png?s=1cd66e5c545917f947b4b838b7bfdeee9105843e)](https://scrutinizer-ci.com/g/wmde/WikibaseInternalSerialization/)
[![Dependency Status](https://www.versioneye.com/php/wikibase:internal-serialization/badge.png)](https://www.versioneye.com/php/wikibase:internal-serialization)

On [Packagist](https://packagist.org/packages/wikibase/internal-serialization):
[![Latest Stable Version](https://poser.pugx.org/wikibase/internal-serialization/version.png)](https://packagist.org/packages/wikibase/internal-serialization)
[![Download count](https://poser.pugx.org/wikibase/internal-serialization/d/total.png)](https://packagist.org/packages/wikibase/internal-serialization)

## Installation

The recommended way to use this library is via [Composer](http://getcomposer.org/).

### Composer

To add this package as a local, per-project dependency to your project, simply add a
dependency on `wikibase/internal-serialization` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on
version 1.x of this package:

    {
        "require": {
            "wikibase/internal-serialization": "1.*"
        }
    }

### Manual

Get the code of this package, either via git, or some other means. Also get all dependencies.
You can find a list of the dependencies in the "require" section of the composer.json file.
Then take care of autoloading the classes defined in the src directory.

## Library usage

Construct an instance of the deserializer or serializer you need via the appropriate factory.

```php
use Wikibase\InternalSerialization\DeserializerFactory;

$deserializerFactory = new DeserializerFactory( /* ... */ );
$entityDeserializer = $deserializerFactory->newEntityDeserializer();
```

The use the deserialize or serialize method.

```php
$entity = $entityDeserializer->deserialize( $myEntitySerialization );
```

In case of deserialization, guarding against failures is good practice.
So it is typically better to use the slightly more verbose try-catch approach.

```php
try {
	$entity = $entityDeserializer->deserialize( $myEntitySerialization );
}
catch ( DeserializationException $ex ) {
	// Handling of the exception
}
```

All access to services provided by this library should happen through the
`SerializerFactory` and `DeserializerFactory`. The rest of the code is an implementation
detail which users are not allowed to know about.

## Library structure

The Wikibase DataModel objects can all be serialized to a generic format from which the objects
can later be reconstructed. This is done via a set of `Serializers\Serializer` implementing objects.
These objects turn for instance a `Claim` object into a data structure containing only primitive
types and arrays. This data structure can thus be readily fed to `json_encode`, `serialize`, or the
like. The process of reconstructing the objects from such a serialization is provided by
objects implementing the `Deserializers\Deserializer` interface.

Serializers can be obtained via an instance of `SerializerFactory` and deserializers can be obtained
via an instance of `DeserializerFactory`. You are not allowed to construct these serializers and
deserializers directly yourself or to have any kind of knowledge of them (ie type hinting). These
objects are internal to this serialization and might change name or structure at any time. All you
are allowed to know when calling `$serializerFactory->newEntitySerializer()` is that you get back
an instance of `Serializers\Serializer`.

The library contains deserializers that handle the legacy internal serialization format. Those
can be found in `Wikibase\InternalSerialization\Deserializers`, and all start with the word "Legacy".
The remaining deserializers in this namespace are not specific to any format. They detect the one
that is used and forward to the appropriate deserializer. These deserializers can thus deal with
serializations in the old legacy format and those in the new one.

The `DeserializerFactory` only returns deserializers that can deal with both the legacy and the
new format.

## Tests

This library comes with a set up PHPUnit tests that cover all non-trivial code. You can run these
tests using the PHPUnit configuration file found in the root directory. The tests can also be run
via TravisCI, as a TravisCI configuration file is also provided in the root directory.

## Authors

Wikibase Internal Serialization has been written by [Jeroen De Dauw]
(https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw), partially as [Wikimedia Germany]
(https://wikimedia.de) employee for the [Wikidata project](https://wikidata.org/).

## Release notes

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

## Links

* [Wikibase Internal Serialization on Packagist](https://packagist.org/packages/wikibase/internal-serialization)
* [Wikibase Internal Serialization on TravisCI](https://travis-ci.org/wmde/WikibaseInternalSerialization)
* [Wikibase Internal Serialization on ScrutinizerCI](https://scrutinizer-ci.com/g/wmde/WikibaseInternalSerialization/)
* [Wikibase Internal Serialization on Ohloh](https://www.ohloh.net/p/WikibaseInternalSerialization)

## See also

* [Wikibase DataModel](https://github.com/wmde/WikibaseDataModel)
* [Wikibase DataModel Serialization](https://github.com/wmde/WikibaseDataModelSerialization) (For the public serialization format)
* [Ask Serialization](https://github.com/wmde/AskSerialization)
