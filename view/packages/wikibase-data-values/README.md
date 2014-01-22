# DataValues Javascript

This component is in alpha state and not suitable for reuse yet.
It contains various JavaScript related to the DataValues library.

[![Build Status](https://secure.travis-ci.org/wmde/DataValuesJavascript.png?branch=master)](http://travis-ci.org/wmde/DataValuesJavascript)

On [Packagist](https://packagist.org/packages/data-values/javascript):
[![Latest Stable Version](https://poser.pugx.org/data-values/javascript/version.png)](https://packagist.org/packages/data-values/javascript)
[![Download count](https://poser.pugx.org/data-values/javascript/d/total.png)](https://packagist.org/packages/data-values/javascript)

## Technical debt

TODO: write high level description and documentation in this README file.

TODO: unify lib and src directories

## Release notes

### 0.2 (dev)

##### Breaking changes

* Renamed dataValues.util.inherit to util.inherit
* #13 Removed vp.GlobeCoordinateParser and vp.QuantityParser
* #15 Removed the ParseValue API module

##### Enhancements

* #14 Decoupled the QUnit tests from the MediaWiki resource loader
* #16 Have the tests run on TravisCI using PhantomJS

### 0.1 (2013-12-23)

Initial release.
