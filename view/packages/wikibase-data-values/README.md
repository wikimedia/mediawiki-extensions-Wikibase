# DataValues Javascript

This component is in alpha state and not suitable for reuse yet.
It contains various JavaScript related to the DataValues library.

[![Build Status](https://secure.travis-ci.org/wmde/DataValuesJavascript.png?branch=master)](http://travis-ci.org/wmde/DataValuesJavascript)

On [Packagist](https://packagist.org/packages/data-values/javascript):
[![Latest Stable Version](https://poser.pugx.org/data-values/javascript/version.png)](https://packagist.org/packages/data-values/javascript)
[![Download count](https://poser.pugx.org/data-values/javascript/d/total.png)](https://packagist.org/packages/data-values/javascript)

## Technical debt

TODO: remove dependency on ApiParserValue from the JS so ApiParseValue
can be removed. ApiParseValue is the only PHP class in this package and
is the only thing that depends on the DataValues PHP components.

TODO: remove dependency on dataTypes.DataType from the JS, so the
data-values/data-types package is no longer required. Right now
only a few lines in ValueParserFactory.js are binding to this for
no good reason.

TODO: write high level description and documentation in this README file.

TODO: make tests runnable without needing MW and a browser

TODO: have tests run on CI

## Release notes

### 0.1 (2013-12-23)

Initial release.
