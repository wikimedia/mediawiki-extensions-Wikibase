# Wikibase JavaScript API

JavaScript client for the Wikibase Repository web API.

[![Latest Stable Version](https://poser.pugx.org/wikibase/javascript-api/version.png)](https://packagist.org/packages/wikibase/javascript-api)

## Release notes

### 1.0.2 (2015-05-20)

#### Enhancements
* Updated code documentation to be able to generate documentation using JSDuck.
* `wikibase.api.RepoApi` QUnit tests have been rewritten to not execute actual API requests anymore.
* Added `wikibase.api.RepoApi` QUnit tests for functions not yet tested.
* Added type checks to `wikibase.api.RepoApi` functions to actually reflect parameter documentation in the code instead of relying on the back-end handling.

### Bugfixes
* An empty `Entity` may be created by omitting the `data` parameter on `wikibase.api.RepoApi.createEntity()` again.
* `wikibase.api.RepoApi` functions explicitly submit default parameters if not set otherwise.

### 1.0.1 (2014-11-28)

* Bump the data-values/javascript dependency to 0.6.0 so that it matches Wikibase.git's.
	No changes needed.

### 1.0 (2014-11-26)

Initial release.
