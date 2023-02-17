# Wikibase REST API

## Configuration

### Enable the REST API

To enable the production-ready routes, add the following line to your `LocalSettings.php` file:

```php
$wgRestAPIAdditionalRouteFiles[] = 'extensions/Wikibase/repo/rest-api/routes.json';
```

To enable routes in development (not recommended for production use), also add:

```php
$wgRestAPIAdditionalRouteFiles[] = 'extensions/Wikibase/repo/rest-api/routes.dev.json';
```

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

The base URL of the API can be configured by passing an `API_URL` environment variable:

```
API_URL='https://wikidata.org/w/rest.php' npm run build:docs
```

The autodocs and the bundled OpenAPI specification files are generated in the `../../docs/rest-api/` directory.

## Development

* @subpage rest_adr_index

### Project structure
This REST API follows the [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/) approach and takes inspiration from [an article about Netflix's use of the hexagonal architecture](https://netflixtechblog.com/ready-for-changes-with-hexagonal-architecture-b315ec967749). This decision is documented in [ADR 0001](docs/adr/0001_hexagonal_architecture.md).

![Hexagonal Architecture Diagram](./hexagonal_architecture.drawio.svg)

#### Directory structure

- `docs/`
  - `adr/`: [Architectural Decision Records](https://adr.github.io/)
- `../../docs/rest-api/`: the built OpenAPI specification and swagger documentation
- `specs/`: OpenAPI specification source
- `src/`
  - `DataAccess/`: implementations of services that bind to persistent storage
  - `Domain/`: domain models and services
  - `Presentation/`: presenter and converter classes to manipulate the output as part of the transport layer
  - `RouteHandlers/` classes that create and pass request DTO into use cases and return HTTP responses
  - `UseCases/`: one directory per use case
- `tests/`
  - `mocha/`: tests using the mocha framework
    - `api-testing/`: end-to-end tests using [MediaWiki's api-testing][1] library
	- `openapi-validation/`: tests against the OpenAPI spec
  - `phpunit/`: integration and unit tests using the phpunit framework

### Tests

#### e2e and schema tests

These tests can be run with the command `npm run api-testing`. They require the targeted wiki to act as both client and repo, so that Items can have sitelinks to pages on the same wiki.

[1]: https://www.mediawiki.org/wiki/MediaWiki_API_integration_tests
