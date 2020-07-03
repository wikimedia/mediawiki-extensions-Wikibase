# Wikibase JavaScript API

JavaScript client for the Wikibase Repository web API.

## Release notes
### 4.0.0 (dev)
* These method have been removed from RepoApi.js
** searchEntities
** createClaim
** getClaims
** setClaimValue
** setReference
** removeReferences

### 3.2.0 (2020-05-13)
* Various improvements to error handling, now requests the `plaintext`
  `errorformat` from the API and explicitly passes a language to use.

### 3.1.1 (2019-04-26)

* Fix getLocationAgnosticMwApi to use browser location not wgServer to fix bugs
  on mobile requests.

### 3.1.0 (2018-11-13)

* Made public method: `wikibase.api.RepoApi.post()`.

### 3.0.2 (2018-11-08)

* Fixed `wikibase.api.RepoApi` to check whether the user
  is still logged in before making any POST requests.

### 3.0.1 (2017-11-01)

* Fixed `wikibase.api.RepoApi.getEntities` to return all properties
  of the entity by default.

### 3.0.0 (2017-10-13)

* Fixed certain `wikibase.api.RepoApi` methods failing when passing in empty strings.
* Made the library a pure JavaScript library.
* Removed MediaWiki extension credits registration.
* Removed MediaWiki ResourceLoader module definitions.
* Removed `WIKIBASE_JAVASCRIPT_API_VERSION` constant.
* Raised DataValues JavaScript library version requirement to 0.10.0.

### 2.2.2 (2017-07-10)

* Fixed inconsistencies in `wikibase.api.RepoApi` introduced in 2.2.1.

### 2.2.1 (2017-07-07)

* Fixed certain `wikibase.api.RepoApi` methods (most notably `parseValue` and `setAliases`) not
  properly accepting values that contain pipe characters.
* ECMAScript 5 is now required. This most notably excludes Internet Explorer 8.

### 2.2.0 (2016-10-31)

* Forward error message parameters in `wikibase.api.RepoApiError`.

### 2.1.1 (2016-08-04)

* Follow up fix to HTML escaping in `wikibase.api.RepoApiError`.

### 2.1.0 (2016-08-03)

* Fixed HTML escaping in `wikibase.api.RepoApiError`.
* Fixed forwarding of error messages in `wikibase.api.FormatValueCaller` and `ParseValueCaller`.
* Removed unused `wikibase-error-ui-client-error` message.

### 2.0.0 (2016-05-31)

* Removed meaningless, unsupported `sort` and `dir` parameters from:
  * `wikibase.api.RepoApi.getEntities`
  * `wikibase.api.RepoApi.getEntitiesByPage`. This only breaks callers using the `normalize` parameter.
* Replaced deprecated "edit" token with "csrf".

### 1.1.1 (2016-05-30)

* Fix getLocationAgnosticMwApi behavior in Internet Explorer

### 1.1.0 (2016-02-17)

* Added optional propertyId parameter to RepoApi::formatValue
* Added optional propertyId parameter to FormatValueCaller::formatValue

### 1.0.5 (2016-01-27)

* Added compatibility with DataValues JavaScript 0.8.0.
* Removed compatibility for Internet Explorer 8 by removing the json polyfill.

### 1.0.4 (2015-09-30)

* Use mw.ForeignApi for remote API endpoints (T50389)
* Pass `uselang` parameter to `wbsearchentities` api call

### 1.0.3 (2015-05-21)

* Made installable with DataValues JavaScript 0.7.0.

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

### 1.0.0 (2014-11-26)

Initial release.
