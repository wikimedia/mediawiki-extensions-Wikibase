# Wikibase REST API

## Configuration

### Enable the REST API

**As of REL1_44, the Wikibase REST API is enabled when the Wikibase repo extension is loaded**

To enable routes in development (not recommended for production use), also add:

```php
$wgRestAPIAdditionalRouteFiles[] = 'extensions/Wikibase/repo/rest-api/routes.dev.json';
```

### Enable Restful Search

Some REST API routes — particularly those related to Items and Properties search — require **Elasticsearch** to be configured and enabled through the CirrusSearch extensions.

If Elasticsearch is not set up, these routes will return an error response.

#### To enable Restfull search functionality:

* Install the WikibaseCirrusSearch, CirrusSearch, and Elastica extensions

* Add the necessary configuration to your LocalSettings.php

## JSON structure changes

* @subpage rest_data_format_differences

## OpenAPI Specification

Our REST API specification is provided using an OpenAPI specification in the `specs` directory. The latest version is published [on doc.wikimedia.org](https://doc.wikimedia.org/Wikibase/master/js/rest-api/).

The specification can be "built" (i.e., compiled into a single JSON OpenAPI specs file) and validated using the provided npm scripts.

To modify API specs, install npm dependencies first, using a command like the following:

```
npm install
```

API specs can be validated using the npm `test` script, using a command like the following:

```
npm test
```

API specs can be bundled into a single file using the npm `build:spec` script, using a command like the following:

```
npm run build:spec
```

Autodocs can be generated from the API specification using the npm `build:docs` script, using a command like the following:

```
npm run build:docs
```

The built page fetches the wiki's served OpenAPI document (`/w/rest.php/wikibase/v1/openapi.json`) at runtime. The host it fetches from can be configured by passing an `OPENAPI_DOC_HOST` environment variable (default: `https://www.wikidata.org`):

```
OPENAPI_DOC_HOST='https://wikibase.example' npm run build:docs
```

Note that wikis send no CORS headers by default, so a page built for a third-party host may need CORS configuration on that wiki. Setting `OPENAPI_DOC_HOST` to an empty string instead produces a server-relative URL for docs hosted on the wiki's own origin, which needs no CORS configuration. This is also the way to view the local wiki's document during development: build with the host empty and open the generated page through the wiki's own web server, e.g. `http://localhost:8080/w/extensions/Wikibase/docs/rest-api/index.html`:

```
OPENAPI_DOC_HOST='' npm run build:docs
```

Alternatively, `npm run serve:docs` starts a webpack dev server on port 7000 that shows the local wiki's document through a proxy, if that port is reachable from your browser.

The autodocs are generated in the `../../docs/rest-api/` directory.

### Working with mwcli

In an [mwcli](https://www.mediawiki.org/wiki/Cli) development environment, run the npm scripts through the fresh container:

```
mw dev mw fresh -- bash -c "cd extensions/Wikibase/repo/rest-api && OPENAPI_DOC_HOST='' npm run build:docs"
```

The built page is then served by the wiki itself, e.g. at `http://default.mediawiki.local.wmftest.net:8080/w/extensions/Wikibase/docs/rest-api/index.html`. The `serve:docs` proxy defaults follow mwcli's conventions and need no configuration, but the fresh container does not publish port 7000, so the dev server is not reachable from a browser on the host — use the static build above instead.

## Versioning

* The _interface_ of the REST API is versioned, not the OpenAPI schema document. This means that changes to the code and OpenAPI schema, that don't change the interface, are allowed without increasing the version.
* Versions will mostly follow the format described by [SemVer 2.0.0]. However, only `MAJOR.MINOR` versions, omitting `.PATCH`, will be created as we see little use for patch versions.
* The version of the REST API is recorded in the `/info/version` field of the OpenAPI schema.
* Changes for each version will be recorded in @subpage wb_rest_api_changelog "CHANGELOG.md".

### Tests

Descriptions of the different kinds of tests can be found in the @ref restApiTestDirs "respective section of the directory structure overview" above.

#### e2e and schema tests

These tests can be run with the command `npm run api-testing`.

The following needs to be correctly set up in order for all the tests to pass:
* the targeted wiki to act as both [client and repo], so that Items can have sitelinks to pages on the same wiki
* a `.api-testing.config` file in `repo/rest-api` (next to this README.md file) - see the [MediaWiki API integration tests] docs
* the [OAuth extension] is installed and configured
* copy the `X-Config-Override` hack from [Wikibase.ci.php] to your `LocalSettings.php`. Do NOT do this on any sort of production wiki.

[client and repo]: @ref docs_topics_repo-client-relationship
[MediaWiki API integration tests]: https://www.mediawiki.org/wiki/MediaWiki_API_integration_tests
[OAuth extension]: https://www.mediawiki.org/wiki/Extension:OAuth
[SemVer 2.0.0]: https://semver.org/spec/v2.0.0.html
[Wikibase.ci.php]: https://github.com/wikimedia/mediawiki-extensions-Wikibase/blob/master/repo/config/Wikibase.ci.php
