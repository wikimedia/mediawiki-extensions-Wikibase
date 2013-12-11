# Wikibase DataModel

[![Latest Stable Version](https://poser.pugx.org/wikibase/data-model/version.png)](https://packagist.org/packages/wikibase/data-model)
[![Latest Stable Version](https://poser.pugx.org/wikibase/data-model/d/total.png)](https://packagist.org/packages/wikibase/data-model)
[![Build Status](https://secure.travis-ci.org/wikimedia/mediawiki-extensions-WikibaseDataModel.png?branch=master)](http://travis-ci.org/wikimedia/mediawiki-extensions-WikibaseDataModel)
[![Coverage Status](https://coveralls.io/repos/wikimedia/mediawiki-extensions-WikibaseDataModel/badge.png?branch=master)](https://coveralls.io/r/wikimedia/mediawiki-extensions-WikibaseDataModel?branch=master)

PHP implementation of the
[Wikibase](https://www.mediawiki.org/wiki/Wikibase)
[Data Model](https://meta.wikimedia.org/wiki/Wikidata/Data_model).

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

## Tests

This library comes with a set up PHPUnit tests that cover all non-trivial code. You can run these
tests using the PHPUnit configuration file found in the root directory. The tests can also be run
via TravisCI, as a TravisCI configuration file is also provided in the root directory.

## Authors

Wikibase DataModel has been written by [Jeroen De Dauw](https://www.mediawiki.org/wiki/User:Jeroen_De_Dauw)
as [Wikimedia Germany](https://wikimedia.de) employee for the [Wikidata project](https://wikidata.org/).

Contributions where also made by [several other people]
(https://www.ohloh.net/p/wikibasedatamodel/contributors?sort=commits).

## Links

* [Wikibase DataModel on Packagist](https://packagist.org/packages/wikibase/data-model)
* [Wikibase DataModel on Ohloh](https://www.ohloh.net/p/wikibasedatamodel/)
* [Wikibase DataModel on MediaWiki.org](https://www.mediawiki.org/wiki/Extension:Wikibase_DataModel)
* [TravisCI build status](https://travis-ci.org/wikimedia/mediawiki-extensions-WikibaseDataModel)
* [Latest version of the readme file](https://github.com/wikimedia/mediawiki-extensions-WikibaseDataModel/blob/master/README.md)
