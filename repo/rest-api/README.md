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

## Versioning

* The _interface_ of the REST API is versioned, not the OpenAPI schema document. This means that changes to the code and OpenAPI schema, that don't change the interface, are allowed without increasing the version.
* Versions will mostly follow the format described by [SemVer 2.0.0]. However, only `MAJOR.MINOR` versions, omitting `.PATCH`, will be created as we see little use for patch versions.
* The version of the REST API is recorded in the `/info/version` field of the OpenAPI schema.
* Changes for each version will be recorded in @subpage wb_rest_api_changelog "CHANGELOG.md".

## Development

* @subpage rest_adr_index

### Project structure
This REST API follows the [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/) approach and takes inspiration from [an article about Netflix's use of the hexagonal architecture](https://netflixtechblog.com/ready-for-changes-with-hexagonal-architecture-b315ec967749). This decision is documented in [ADR 0001](docs/adr/0001_hexagonal_architecture.md).

\image{inline} html ./hexagonal_architecture.drawio.svg "Hexagonal Architecture Diagram"

The code is divided into three layers: Domain, Application, and Infrastructure. Domain and Application define the core business and application logic of the software, whereas the infrastructure layer deals with any external dependencies and concepts, such as transport or persistence details.

#### Directory structure

- `docs/`
  - `adr/`: [Architectural Decision Records](https://adr.github.io/)
- `../../docs/rest-api/`: the built OpenAPI specification and swagger documentation
- `specs/`: OpenAPI specification source
- `src/`
  - `Domain/`
    - `Model/`: Entities and value objects used when persisting data
    - `ReadModel/`: Entities and value objects used when retrieving data
    - `Services/`: Secondary ports, i.e. persistence interfaces such as retrievers and updaters
  - `Application/`
    - `Serialization/`: Deserializers used for turning user input into write models, serializers used for turning read models into JSON-serializable objects
    - `Validation/`: Generic (not use cases specific) classes for validating user input
    - `UseCases/`: Primary ports of the application core
  - `Infrastructure/`: Secondary adapters, i.e. implementations of interfaces defined in the application core
    - `DataAccess/`: Implementations of persistence services
  - `RouteHandlers/`: Web controllers acting as primary adapters
- `tests/` @anchor restApiTestDirs
  - `mocha/`: tests using the mocha framework
    - `api-testing/`: end-to-end tests using [MediaWiki's api-testing][1] library
    - `openapi-validation/`: tests against the OpenAPI spec
  - `phpunit/`: integration and unit tests using the phpunit framework

### Tests

Descriptions of the different kinds of tests can be found in the @ref restApiTestDirs "respective section of the directory structure overview" above.

#### e2e and schema tests

These tests can be run with the command `npm run api-testing`.

The following needs to be correctly set up in order for all the tests to pass:
* the targeted wiki to act as both [client and repo], so that Items can have sitelinks to pages on the same wiki
* a `.api-testing.config` file in `repo/rest-api` (next to this README.md file) - see the [MediaWiki API integration tests] docs
* the [OAuth extension] is installed and configured
* copy the `X-Config-Override` hack from [Wikibase.ci.php] to your `LocalSettings.php`. Do NOT do this on any sort of production wiki.

[1]: https://www.mediawiki.org/wiki/MediaWiki_API_integration_tests
[client and repo]: @ref docs_topics_repo-client-relationship
[MediaWiki API integration tests]: https://www.mediawiki.org/wiki/MediaWiki_API_integration_tests
[OAuth extension]: https://www.mediawiki.org/wiki/Extension:OAuth
[SemVer 2.0.0]: https://semver.org/spec/v2.0.0.html
[Wikibase.ci.php]: https://github.com/wikimedia/mediawiki-extensions-Wikibase/blob/master/repo/config/Wikibase.ci.php
