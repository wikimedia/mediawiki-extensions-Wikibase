# Wikibase DataModel

[![Build Status](https://secure.travis-ci.org/wmde/WikibaseDataModel.png?branch=master)](http://travis-ci.org/wmde/WikibaseDataModel)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel/?branch=master)
[![Dependency Status](https://www.versioneye.com/php/wikibase:data-model/dev-master/badge.svg)](https://www.versioneye.com/php/wikibase:data-model/dev-master)
[![Download count](https://poser.pugx.org/wikibase/data-model/d/total.png)](https://packagist.org/packages/wikibase/data-model)
[![License](https://poser.pugx.org/wikibase/data-model/license.svg)](https://packagist.org/packages/wikibase/data-model)

[![Latest Stable Version](https://poser.pugx.org/wikibase/data-model/version.png)](https://packagist.org/packages/wikibase/data-model)
[![Latest Unstable Version](https://poser.pugx.org/wikibase/data-model/v/unstable.svg)](//packagist.org/packages/wikibase/data-model)

**Wikibase DataModel** is the canonical PHP implementation of the
[Data Model](https://meta.wikimedia.org/wiki/Wikidata/Data_model)
at the heart of the [Wikibase software](http://wikiba.se/).

It is primarily used by the Wikibase MediaWiki extensions, though
has no dependencies whatsoever on these or on MediaWiki itself.

Recent changes can be found in the [release notes](RELEASE-NOTES.md).

## Installation

You can use [Composer](http://getcomposer.org/) to download and install
this package as well as its dependencies. Alternatively you can simply clone
the git repository and take care of loading yourself.

### Composer

To add this package as a local, per-project dependency to your project, simply add a
dependency on `wikibase/data-model` to your project's `composer.json` file.
Here is a minimal example of a `composer.json` file that just defines a dependency on
Wikibase DataModel 2.0:

```js
{
    "require": {
        "wikibase/data-model": "2.0.*"
    }
}
```

### Manual

Get the Wikibase DataModel code, either via git, or some other means. Also get all dependencies.
You can find a list of the dependencies in the "require" section of the composer.json file.
The "autoload" section of this file specifies how to load the resources provide by this library.

## Library contents

This library contains domain objects that implement the concepts part of the
[Wikibase DataModel](https://www.mediawiki.org/wiki/Wikibase/DataModel).
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

The initial [conceptual specification](https://www.mediawiki.org/wiki/Wikibase/DataModel)
for the DataModel was created by [Markus Krötzsch](http://korrekt.org/)
and [Denny Vrandečić](http://simia.net/wiki/Denny), with minor contributions by
Daniel Kinzler and Jeroen De Dauw.

## Links

* [Wikibase DataModel on Packagist](https://packagist.org/packages/wikibase/data-model)
* [Wikibase DataModel on Ohloh](https://www.ohloh.net/p/wikibasedatamodel/)
* [Wikibase DataModel on TravisCI](https://travis-ci.org/wmde/WikibaseDataModel)
* [Wikibase DataModel on ScrutinizerCI](https://scrutinizer-ci.com/g/wmde/WikibaseDataModel)
 
## See also

* [Blog posts on Wikibase DataModel](http://www.bn2vs.com/blog/tag/wikibase-datamodel/)
* [Wikibase DataModel Serialization](https://github.com/wmde/WikibaseDataModelSerialization)
* [Wikibase Internal Serialization](https://github.com/wmde/WikibaseInternalSerialization)
