# Wikibase Internal Serialization

Library containing serializers and deserializers for the data access layer of [Wikibase](http://wikiba.se/) Repository.

[![Build Status](https://github.com/wmde/WikibaseInternalSerialization/actions/workflows/lint-and-test.yaml/badge.svg?branch=master)](https://github.com/wmde/WikibaseInternalSerialization/actions/workflows/lint-and-test.yaml)

On [Packagist](https://packagist.org/packages/wikibase/internal-serialization):
[![Latest Stable Version](https://poser.pugx.org/wikibase/internal-serialization/version.png)](https://packagist.org/packages/wikibase/internal-serialization)
[![Download count](https://poser.pugx.org/wikibase/internal-serialization/d/total.png)](https://packagist.org/packages/wikibase/internal-serialization)

Note that this repository is a mirror of part of the upstream [Wikibase project](https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Wikibase/+/refs/heads/master/lib/packages/wikibase/internal-serialization/) on Gerrit.
Contributions should be made to the directories there using MediaWiki's [Gerrit process](https://www.mediawiki.org/wiki/Gerrit).

## Installation

The recommended way to use this library is via [Composer](http://getcomposer.org/).

### Composer

To add this package as a local, per-project dependency to your project, simply add a
dependency on `wikibase/internal-serialization` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on
version 2.x of this package:

    {
        "require": {
            "wikibase/internal-serialization": "~2.0"
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

## Contributing

This repository is a mirror of part of the upstream [Wikibase project](https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Wikibase/+/refs/heads/master/lib/packages/wikibase/internal-serialization/) on Gerrit.
Contributions should be made to the directories there using MediaWiki's [Gerrit process](https://www.mediawiki.org/wiki/Gerrit).

## Authors

Wikibase Internal Serialization has been written by
[Jeroen De Dauw](https://entropywins.wtf/), partially as
[Wikimedia Germany](https://wikimedia.de/en) employee for the [Wikidata project](https://wikidata.org/).

## Links

* [Wikibase Internal Serialization on Packagist](https://packagist.org/packages/wikibase/internal-serialization)
* [Wikibase Internal Serialization on OpenHub](https://www.openhub.net/p/WikibaseInternalSerialization)

## See also

* [Wikibase DataModel](https://github.com/wmde/WikibaseDataModel)
* [Wikibase DataModel Serialization](https://github.com/wmde/WikibaseDataModelSerialization) (For the public serialization format)
* [Ask Serialization](https://github.com/wmde/AskSerialization)
