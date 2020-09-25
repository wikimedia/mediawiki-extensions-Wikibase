# Wikibase DataModel Serialization

[![Build Status](https://travis-ci.org/wmde/WikibaseDataModelSerialization.svg?branch=master)](https://travis-ci.org/wmde/WikibaseDataModelSerialization)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/badges/quality-score.png?s=d56b9477c29f4799b3834c4fbcc3731687feae95)](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/)
[![Code Coverage](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/badges/coverage.png?s=916d21028b031abe2e685192ccef46c6f47ba76a)](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/)
[![Download count](https://poser.pugx.org/wikibase/data-model-serialization/d/total.png)](https://packagist.org/packages/wikibase/data-model-serialization)
[![License](https://poser.pugx.org/wikibase/data-model-serialization/license.svg)](https://packagist.org/packages/wikibase/data-model-serialization)

[![Latest Stable Version](https://poser.pugx.org/wikibase/data-model-serialization/version.png)](https://packagist.org/packages/wikibase/data-model-serialization)
[![Latest Unstable Version](https://poser.pugx.org/wikibase/data-model-serialization/v/unstable.svg)](//packagist.org/packages/wikibase/data-model-serialization)

Library containing serializers and deserializers for the basic
[Wikibase DataModel](https://github.com/wmde/WikibaseDataModel) entity types and components they are
made of.
The supported formats are limited to public ones, ie those used by a web API.
Serialization code for private formats, such as the format used by the Wikibase
Repo data access layer, belongs in other components.

Recent changes can be found in the [release notes](RELEASE-NOTES.md).

## Installation

The recommended way to use this library is via [Composer](http://getcomposer.org/).

### Composer

To add this package as a local, per-project dependency to your project, simply add a
dependency on `wikibase/data-model-serialization` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on
version 2.0 of this package:

```json
{
	"require": {
		"wikibase/data-model-serialization": "~2.0"
	}
}
```

### Manual

Get the code of this package, either via git, or some other means. Also get all dependencies.
You can find a list of the dependencies in the "require" section of the composer.json file.
Then take care of autoloading the classes defined in the src directory.

## Library usage

Construct an instance of the specific deserializer or serializer you need via the appropriate factory.

```php
use Wikibase\DataModel\DeserializerFactory;

$deserializerFactory = new DeserializerFactory( /* ... */ );
$itemDeserializer = $deserializerFactory->newItemDeserializer();
```

Then use the `deserialize` or `serialize` method.

```php
$item = $itemDeserializer->deserialize( $myItemSerialization );
```

In case of deserialization, guarding against failures is good practice.
So it is typically better to use the slightly more verbose try-catch approach.

```php
try {
	$item = $itemDeserializer->deserialize( $myItemSerialization );
} catch ( DeserializationException $ex ) {
	// Handling of the exception
}
```

All access to services provided by this library should happen through the
SerializerFactory and DeserializerFactory. The rest of the code is an implementation
detail which users should not know about.

## Library structure

The Wikibase DataModel objects can all be serialized to a generic format from which the objects
can later be reconstructed. This is done via a set of `Serializers\Serializer` implementing objects.
These objects turn for instance a `Statement` object into a data structure containing only primitive
types and arrays. This data structure can thus be readily fed to `json_encode`, `serialize`, or the
like. The process of reconstructing the objects from such a serialization is provided by
objects implementing the `Deserializers\Deserializer` interface.

Serializers can be obtained via an instance of `SerializerFactory` and deserializers can be obtained
via an instance of `DeserializerFactory`. You are not allowed to construct these serializers and
deserializers directly yourself or to have any kind of knowledge of them (ie type hinting). These
objects are internal to this component and might change name or structure at any time. All you
are allowed to know when calling `$serializerFactory->newItemDeserializer()` is that you get back
an instance of `Serializers\Serializer`.

## Running the tests

For tests only

    composer test

For style checks only

	composer cs

For a full CI run

	composer ci


## Authors

Wikibase DataModel Serialization has been written by [Thomas PT](https://github.com/Tpt) as volunteer
and by [Jeroen De Dauw](https://www.EntropyWins.wtf) as
[Wikimedia Germany](https://wikimedia.de) employee for the [Wikidata project](https://wikidata.org/).

## Links

* [Wikibase DataModel Serialization on Packagist](https://packagist.org/packages/wikibase/data-model-serialization)
* [Wikibase DataModel Serialization on TravisCI](https://travis-ci.org/wmde/WikibaseDataModelSerialization)
* [Wikibase DataModel Serialization on ScrutinizerCI](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/)
* [Wikibase DataModel Serialization on OpenHub](https://www.openhub.net/p/WikibaseDataModelSerialization)

## See also 

* [Wikibase DataModel](https://github.com/wmde/WikibaseDataModel)
* [Wikibase Internal Serialization](https://github.com/wmde/WikibaseInternalSerialization) (For the "internal" serialization format)

# Bugs on Phabricator

https://phabricator.wikimedia.org/project/view/922/
