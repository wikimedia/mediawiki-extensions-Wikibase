# Wikibase DataModel Serialization

Library containing serializers and deserializers for the Wikibase DataModel.
The supported formats are limited to public ones, ie those used by a web API.
Serialization code for private formats, such as the format used by the Wikibase
Repo data access layer, belongs in other components.

[![Build Status](https://secure.travis-ci.org/wmde/WikibaseDataModelSerialization.png?branch=master)](http://travis-ci.org/wmde/WikibaseDataModelSerialization)
[![Code Coverage](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/badges/coverage.png?s=916d21028b031abe2e685192ccef46c6f47ba76a)](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/badges/quality-score.png?s=d56b9477c29f4799b3834c4fbcc3731687feae95)](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/)

On [Packagist](https://packagist.org/packages/wikibase/data-model-serialization):
[![Latest Stable Version](https://poser.pugx.org/wikibase/data-model-serialization/version.png)](https://packagist.org/packages/wikibase/data-model-serialization)
[![Download count](https://poser.pugx.org/wikibase/data-model-serialization/d/total.png)](https://packagist.org/packages/wikibase/data-model-serialization)

## Installation

The recommended way to use this library is via [Composer](http://getcomposer.org/).

### Composer

To add this package as a local, per-project dependency to your project, simply add a
dependency on `wikibase/data-model-serialization` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on
version 1.0 of this package:

    {
        "require": {
            "wikibase/data-model-serialization": "1.0.*"
        }
    }

### Manual

Get the code of this package, either via git, or some other means. Also get all dependencies.
You can find a list of the dependencies in the "require" section of the composer.json file.
Then take care of autoloading the classes defined in the src directory.

## Library functionality

TODO

## Tests

This library comes with a set up PHPUnit tests that cover all non-trivial code. You can run these
tests using the PHPUnit configuration file found in the root directory. The tests can also be run
via TravisCI, as a TravisCI configuration file is also provided in the root directory.

## Authors

Wikibase DataModel has been written by [Jeroen De Dauw](https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw)
as [Wikimedia Germany](https://wikimedia.de) employee for the [Wikidata project](https://wikidata.org/).

## Release notes

### 0.1 (dev)

* 

## Links

* [Wikibase DataModel Serialization on Packagist](https://packagist.org/packages/wikibase/data-model-serialization)
* [Wikibase DataModel Serialization on TravisCI](https://travis-ci.org/wmde/WikibaseDataModelSerialization)
* [Wikibase DataModel Serialization on ScrutinizerCI](https://scrutinizer-ci.com/g/wmde/WikibaseDataModelSerialization/)
* [Wikibase DataModel](https://github.com/wmde/WikibaseDataModel)
