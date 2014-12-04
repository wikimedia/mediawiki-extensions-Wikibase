# Wikibase JavaScript API

## Release notes

### 1.0.2 (dev)

#### Enhancements
* Updated code documentation to be able to generate documentation using JSDuck.
* `wikibase.api.RepoApi` QUnit tests have been rewritten to not execute actual API requests anymore.

### Bugfixes
* An empty `Entity` may be created by omitting the `data` parameter on `wikibase.api.RepoApi.createEntity()` again.
* `wikibase.api.RepoApi` always submits `normalize` parameter if it is specified explicitly (before, `false` resolved to `undefined`).

### 1.0.1 (2014-11-28)

* Bump the data-values/javascript dependency to 0.6.0 so that it matches Wikibase.git's.
	No changes needed.

### 1.0 (2014-11-26)

Initial release.
