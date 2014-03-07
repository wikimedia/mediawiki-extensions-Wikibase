# Wikibase DataModel

PHP implementation of the
[Wikibase](https://www.mediawiki.org/wiki/Wikibase)
[Data Model](https://meta.wikimedia.org/wiki/Wikidata/Data_model).
This implementation depends on a number of standalone PHP libraries (see below),
though does not depend on either MediaWiki or the Wikibase Repo/Client software.

[![Build Status](https://secure.travis-ci.org/wmde/WikibaseDataModel.png?branch=master)](http://travis-ci.org/wmde/WikibaseDataModel)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel/badges/quality-score.png?s=6e63826e875923969a3b5f9bbd03f79839b835a5)](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel/)
[![Code Coverage](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel/badges/coverage.png?s=a48a587bb3fd2705cbe3137e8361fc7c3408a9af)](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel/)
[![Dependency Status](https://www.versioneye.com/php/wikibase:data-model/badge.png)](https://www.versioneye.com/php/wikibase:data-model)

On Packagist:
[![Latest Stable Version](https://poser.pugx.org/wikibase/data-model/version.png)](https://packagist.org/packages/wikibase/data-model)
[![Download count](https://poser.pugx.org/wikibase/data-model/d/total.png)](https://packagist.org/packages/wikibase/data-model)

Recent changes can be found in the [release notes](RELEASE-NOTES.md).

## Installation

You can use [Composer](http://getcomposer.org/) to download and install
this package as well as its dependencies. Alternatively you can simply clone
the git repository and take care of loading yourself.

### Composer

To add this package as a local, per-project dependency to your project, simply add a
dependency on `wikibase/data-model` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on
Wikibase DataModel 1.0:

    {
        "require": {
            "wikibase/data-model": "1.0.*"
        }
    }

### Manual

Get the Wikibase DataModel code, either via git, or some other means. Also get all dependencies.
You can find a list of the dependencies in the "require" section of the composer.json file.
The "autoload" section of this file specifies how to load the resources provide by this library.

## Library contents

This library contains domain objects that implement the concepts part of the
[Wikibase DataModel](https://meta.wikimedia.org/wiki/Wikidata/Data_model).
This mainly includes simple value objects, though also contains core domain
logic either bound to such objects or encapsulated as service objects.

## Tests

This library comes with a set up PHPUnit tests that cover all non-trivial code. You can run these
tests using the PHPUnit configuration file found in the root directory. The tests can also be run
via TravisCI, as a TravisCI configuration file is also provided in the root directory.

## Credits

### Development

Wikibase DataModel has been written by [Jeroen De Dauw](https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw)
as [Wikimedia Germany](https://wikimedia.de) employee for the [Wikidata project](https://wikidata.org/).

Contributions where also made by [several other people]
(https://www.ohloh.net/p/wikibasedatamodel/contributors?sort=commits).

### Concept

The initial [conceptual specification](https://meta.wikimedia.org/wiki/Wikidata/Data_model)
for the DataModel was created by [Markus Krötzsch](http://korrekt.org/)
and [Denny Vrandečić](http://simia.net/wiki/Denny), with minor contributions by
Daniel Kinzler and Jeroen De Dauw.

## Links

* [Wikibase DataModel on Packagist](https://packagist.org/packages/wikibase/data-model)
* [Wikibase DataModel on Ohloh](https://www.ohloh.net/p/wikibasedatamodel/)
* [Wikibase DataModel on TravisCI](https://travis-ci.org/wmde/WikibaseDataModel)
* [Wikibase DataModel on ScrutinizerCI](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel)
 
## See also

* [Wikibase DataModel Serialization](https://github.com/wmde/WikibaseDataModelSerialization)
* [Wikibase Internal Serialization](https://github.com/wmde/WikibaseInternalSerialization)
